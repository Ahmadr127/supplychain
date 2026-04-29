<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UserImportController extends Controller
{
    /**
     * Extract first and last name only (without middle names and titles)
     * Takes text before comma, then extracts first and last word
     */
    private function extractNameWithoutTitle($fullName)
    {
        // Get text before the first comma
        $parts = explode(',', $fullName);
        $name = trim($parts[0]);
        
        // Split by spaces and get first and last word
        $words = array_filter(explode(' ', $name));
        $words = array_values($words); // Re-index array
        
        if (count($words) == 0) {
            return '';
        } elseif (count($words) == 1) {
            return $words[0];
        } else {
            // Return first and last name only
            return $words[0] . ' ' . $words[1];
        }
    }

    public function showImportForm()
    {
        return view('users.import');
    }

    public function import(Request $request)
    {
        try {
            // Validate file
            $validated = $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
                'set_as_head' => 'nullable|boolean',
            ]);

            $file = $request->file('file');
            
            if (!$file) {
                return redirect()->route('users.import')
                    ->with('error', 'File tidak ditemukan');
            }

            $setAsHead = $request->boolean('set_as_head');

            DB::beginTransaction();

            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row
            $headerRow = array_map(function($val) {
                return strtolower(trim((string)$val));
            }, $rows[0]);

            // Default indices if headers mismatch (fallback to new template format)
            $nipIdx = array_search('nip', $headerRow);
            $nipIdx = $nipIdx !== false ? $nipIdx : 1;

            $nameIdx = array_search('nama karyawan', $headerRow);
            $nameIdx = $nameIdx !== false ? $nameIdx : 2;

            $orgNameIdx = array_search('organisasi', $headerRow);
            $orgNameIdx = $orgNameIdx !== false ? $orgNameIdx : 3;

            $orgCodeIdx = array_search('kode organisasi', $headerRow);
            // If code not found, assume user uploaded old format
            
            $posIdx = array_search('posisi pekerjaan', $headerRow);
            $posIdx = $posIdx !== false ? $posIdx : ($orgCodeIdx !== false ? 5 : 4);

            $roleIdx = array_search('jabatan', $headerRow);
            $roleIdx = $roleIdx !== false ? $roleIdx : ($orgCodeIdx !== false ? 6 : 5);

            array_shift($rows);

            $imported = 0;
            $errors = [];
            
            // Get manager permissions for new roles
            $managerRole = Role::where('name', 'manager')->first();
            $managerPermissions = $managerRole ? $managerRole->permissions()->pluck('permissions.id')->toArray() : [];
            
            // Fallback to dashboard permission if manager role not found
            if (empty($managerPermissions)) {
                $managerPermissions = Permission::whereIn('name', ['view_dashboard'])->pluck('id')->toArray();
            }

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    $nik = trim($row[$nipIdx] ?? '');
                    $name = trim($row[$nameIdx] ?? '');
                    $organizationName = trim($row[$orgNameIdx] ?? '');
                    $organizationCode = $orgCodeIdx !== false ? trim($row[$orgCodeIdx] ?? '') : '';
                    $position = trim($row[$posIdx] ?? '');
                    $roleName = trim($row[$roleIdx] ?? 'staff');

                    if (!$nik || !$name) {
                        $errors[] = "Baris {$rowNumber}: NIK dan Nama wajib diisi";
                        continue;
                    }

                    // Find or create department
                    $department = null;
                    if ($organizationName || $organizationCode) {
                        // Priority 1: Find by Code if provided
                        if ($organizationCode) {
                            $department = Department::where('code', $organizationCode)->first();
                        }
                        
                        // Priority 2: Find by Name if Code not found or not provided
                        if (!$department && $organizationName) {
                            $department = Department::where('name', $organizationName)->first();
                        }
                        
                        if (!$department && $organizationName) {
                            // If user doesn't provide code, generate one from name
                            $baseCode = $organizationCode ? $organizationCode : strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $organizationName), 0, 10));
                            $code = $baseCode;
                            $counter = 1;
                            
                            while (Department::where('code', $code)->exists()) {
                                $code = $baseCode . $counter;
                                $counter++;
                            }
                            
                            $department = Department::create([
                                'name' => $organizationName,
                                'code' => $code,
                                'description' => $organizationName,
                                'is_active' => true,
                            ]);
                        } elseif ($department && $organizationCode && $department->code !== $organizationCode) {
                            // Optional: Update the code if it was found by name and a new code is provided
                            $department->update(['code' => $organizationCode]);
                        }
                    }

                    // Find or create role
                    $roleSlug = strtolower(str_replace(' ', '_', $roleName));
                    $role = Role::where('name', $roleSlug)->first();
                    
                    if (!$role) {
                        $role = Role::create([
                            'name' => $roleSlug,
                            'display_name' => $roleName,
                            'description' => "Role {$roleName}",
                        ]);
                        
                        // Assign manager permissions to new role
                        if (!empty($managerPermissions)) {
                            $role->permissions()->sync($managerPermissions);
                        }
                    }

                    // Generate username from name (without titles)
                    $nameWithoutTitle = $this->extractNameWithoutTitle($name);
                    $username = strtolower(str_replace(' ', '.', preg_replace('/[^A-Za-z0-9\s]/', '', $nameWithoutTitle)));
                    $baseUsername = $username;
                    $counter = 1;
                    
                    while (User::where('username', $username)->where('nik', '!=', $nik)->exists()) {
                        $username = $baseUsername . $counter;
                        $counter++;
                    }

                    // Generate email
                    $email = $username . '@azra.com';
                    $counter = 1;
                    while (User::where('email', $email)->where('nik', '!=', $nik)->exists()) {
                        $email = $baseUsername . $counter . '@azra.com';
                        $counter++;
                    }

                    // Create or update user
                    $user = User::where('nik', $nik)->first();

                    if ($user) {
                        $user->update([
                            'name' => $name,
                            'username' => $username,
                            'email' => $email,
                            'role_id' => $role->id,
                        ]);
                    } else {
                        $user = User::create([
                            'nik' => $nik,
                            'name' => $name,
                            'username' => $username,
                            'email' => $email,
                            'password' => Hash::make('rsazra'),
                            'role_id' => $role->id,
                        ]);
                    }

                    // Attach to department
                    if ($department) {
                        // Detach ALL previous departments to avoid duplication if user moves department
                        $user->departments()->detach();
                        
                        $user->departments()->attach($department->id, [
                            'position' => $position,
                            'is_primary' => true,
                            'is_manager' => $setAsHead,
                            'start_date' => now(),
                        ]);

                        if ($setAsHead) {
                            $department->update(['manager_id' => $user->id]);
                        }
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                }
            }

            DB::commit();

            if (!empty($errors)) {
                return redirect()->route('users.import')
                    ->with('warning', "Import selesai dengan {$imported} user berhasil. Beberapa error: " . implode('; ', array_slice($errors, 0, 5)));
            }

            return redirect()->route('users.index')
                ->with('success', "Berhasil mengimport {$imported} user");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('users.import')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('users.import')
                ->with('error', 'Gagal mengimport file: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = ['NO', 'NIP', 'Nama Karyawan', 'Organisasi', 'Kode Organisasi', 'Posisi Pekerjaan', 'Jabatan'];
        $sheet->fromArray($headers, null, 'A1');

        // Add sample data
        $sampleData = [
            [1, '20141969', 'DIENI ANANDA PUTRI, DR., MARS', 'MUTU', 'MUTU', 'MANAGER MUTU', 'MANAGER'],
            [2, '20061105', 'GARCINIA SATIVA FIZRIA SETIADI, Dr, MKM', 'PENUNJANG MEDIK', 'PENJMED', 'MANAGER PENUNJANG MEDIK', 'MANAGER'],
            [3, '20253017', 'INDRA THALIB, B.SN., MM', 'SDM', 'SDM', 'MANAGER SDM', 'MANAGER'],
        ];
        $sheet->fromArray($sampleData, null, 'A2');

        // Style header
        $headerStyle = $sheet->getStyle('A1:G1');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');

        // Auto size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        
        $filename = 'template_import_users.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}

