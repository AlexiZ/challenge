<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    public function __construct(
        private readonly string $projectDir,
    ) {}

    /**
     * Uploads $file under public/uploads/$subDir with a random filename, and returns
     * the stored path relative to that subdirectory. Deletes $existingRelativePath
     * (relative to the same subdirectory) first, if given.
     */
    public function upload(UploadedFile $file, string $subDir, ?string $existingRelativePath = null): string
    {
        $uploadsDir = $this->projectDir . '/public/uploads/' . $subDir;

        if (null !== $existingRelativePath) {
            $this->delete($subDir, $existingRelativePath);
        }

        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0775, true);
        }

        $filename = bin2hex(random_bytes(8)) . '.' . $file->guessExtension();
        $file->move($uploadsDir, $filename);

        return $filename;
    }

    public function delete(string $subDir, string $relativePath): void
    {
        $path = $this->projectDir . '/public/uploads/' . $subDir . '/' . $relativePath;
        if (is_file($path)) {
            unlink($path);
        }
    }
}
