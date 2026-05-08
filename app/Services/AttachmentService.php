<?php

namespace App\Services;

use App\Models\ApprovalItemStep;
use App\Models\ApprovalItemStepAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * AttachmentService
 *
 * Layanan terpusat untuk mengelola lampiran approval step.
 * Dapat dipanggil dari Web Controller maupun API Controller.
 *
 * Upload lampiran bersifat NULLABLE — tidak wajib meskipun step punya required_action 'upload_attachment'.
 */
class AttachmentService
{
    /**
     * Simpan satu atau banyak lampiran untuk sebuah step.
     *
     * @param  ApprovalItemStep   $step
     * @param  UploadedFile[]     $files  Array of UploadedFile instances
     * @param  int|null           $uploadedBy  User ID (default: auth()->id())
     * @return Collection<ApprovalItemStepAttachment>
     */
    public function storeStepAttachments(
        ApprovalItemStep $step,
        array $files,
        ?int $uploadedBy = null
    ): Collection {
        $uploadedBy ??= Auth::id();
        $saved = collect();

        foreach ($files as $file) {
            if (!($file instanceof UploadedFile) || !$file->isValid()) {
                continue;
            }

            $path = $file->store('step_attachments', 'public');

            $attachment = ApprovalItemStepAttachment::create([
                'approval_item_step_id'    => $step->id,
                'approval_request_id'      => $step->approval_request_id,
                'approval_request_item_id' => $step->approval_request_item_id,
                'original_name'            => $file->getClientOriginalName(),
                'path'                     => $path,
                'mime'                     => $file->getClientMimeType(),
                'size'                     => $file->getSize(),
                'uploaded_by'              => $uploadedBy,
            ]);

            $saved->push($attachment);
        }

        return $saved;
    }

    /**
     * Hapus semua lampiran dari sebuah step.
     * Menghapus file fisik dari storage dan record dari database.
     */
    public function deleteStepAttachments(ApprovalItemStep $step): void
    {
        foreach ($step->attachments as $attachment) {
            if (Storage::disk('public')->exists($attachment->path)) {
                Storage::disk('public')->delete($attachment->path);
            }
            $attachment->delete();
        }
    }

    /**
     * Dapatkan URL publik sebuah lampiran.
     */
    public function getAttachmentUrl(ApprovalItemStepAttachment $attachment): string
    {
        return Storage::disk('public')->url($attachment->path);
    }

    /**
     * Format lampiran untuk response API.
     * Mengembalikan array yang siap di-serialize ke JSON.
     *
     * @param  Collection<ApprovalItemStepAttachment>  $attachments
     */
    public function formatAttachmentsForApi(Collection $attachments): array
    {
        return $attachments->map(function (ApprovalItemStepAttachment $a) {
            return [
                'id'            => $a->id,
                'original_name' => $a->original_name,
                'mime'          => $a->mime,
                'size'          => $a->size,
                'size_formatted'=> $a->formattedSize(),
                'url'           => route('api.step-attachments.view', $a->id),
                'download_url'  => route('api.step-attachments.download', $a->id),
                'uploaded_by'   => $a->uploader?->name,
                'uploaded_at'   => $a->created_at?->toIso8601String(),
            ];
        })->values()->all();
    }
}
