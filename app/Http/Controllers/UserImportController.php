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
            array_shift($rows);

            $imported = 0;
            $errors = [];
            $staffPermissions = Permission::whereIn('name', ['view_dashboard'])->pluck('id')->toArray();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    $nik = trim($row[1] ?? '');
                    $name = trim($row[2] ?? '');
                    $organizationName = trim($row[3] ?? '');
                    $position = trim($row[4] ?? '');
                    $roleName = trim($row[5] ?? 'staff');

                    if (!$nik || !$name) {
                        $errors[] = "Baris {$rowNumber}: NIK dan Nama wajib diisi";
                        continue;
                    }

                    // Find or create department
                    $department = null;
                    if ($organizationName) {
                        $department = Department::where('name', $organizationName)->first();
                        
                        if (!$department) {
                            $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $organizationName), 0, 10));
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
                        
                        if (!empty($staffPermissions)) {
                            $role->permissions()->sync($staffPermissions);
                        }
                    }

                    // Generate username from name
                    $username = strtolower(str_replace(' ', '.', preg_replace('/[^A-Za-z0-9\s]/', '', $name)));
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
                            'password' => Hash::make('password'),
                            'role_id' => $role->id,
                        ]);
                    }

                    // Attach to department
                    if ($department) {
                        $user->departments()->detach($department->id);
                        
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
        $headers = ['NO', 'NIP', 'Nama Karyawan', 'Organisasi', 'Posisi Pekerjaan', 'Jabatan'];
        $sheet->fromArray($headers, null, 'A1');

        // Add sample data
        $sampleData = [
            [1, '20141969', 'DIENI ANANDA PUTRI, DR., MARS', 'MUTU', 'MANAGER MUTU', 'MANAGER'],
            [2, '20061105', 'GARCINIA SATIVA FIZRIA SETIADI, Dr, MKM', 'PENUNJANG MEDIK', 'MANAGER PENUNJANG MEDIK', 'MANAGER'],
            [3, '20253017', 'INDRA THALIB, B.SN., MM', 'SDM', 'MANAGER SDM', 'MANAGER'],
        ];
        $sheet->fromArray($sampleData, null, 'A2');

        // Style header
        $headerStyle = $sheet->getStyle('A1:F1');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');

        // Auto size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        
        $filename = 'template_import_users.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
