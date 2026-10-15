<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    /**
     * Upload a file and return its path.
     */
    public function upload(UploadedFile $file, string $directory, string $prefix = 'file'): string
    {
        $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($directory, $filename, 'public');
    }

    /**
     * Delete a file if it exists.
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Delete old file and upload a new one.
     */
    public function replace(UploadedFile $file, ?string $oldPath, string $directory, string $prefix = 'file'): string
    {
        $this->delete($oldPath);
        return $this->upload($file, $directory, $prefix);
    }
}
