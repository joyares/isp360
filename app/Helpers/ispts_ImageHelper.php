<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

class ispts_ImageHelper
{
    public function ispts_compress(array $uploadedFile, ?string $targetDirectory = null): string
    {
        if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Image upload failed or no file was provided.');
        }

        $sourcePath = (string) ($uploadedFile['tmp_name'] ?? '');
        if ($sourcePath === '' || !is_file($sourcePath)) {
            throw new RuntimeException('Uploaded image temporary file was not found.');
        }

        $targetDirectory = $targetDirectory
            ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads';

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        $imageInfo = getimagesize($sourcePath);
        if ($imageInfo === false) {
            throw new RuntimeException('Invalid image file.');
        }

        [$originalWidth, $originalHeight, $imageType] = $imageInfo;
        $sourceImage = match ($imageType) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if ($sourceImage === false) {
            throw new RuntimeException('Unsupported image format.');
        }

        $maxWidth = 1200;
        $newWidth = $originalWidth > $maxWidth ? $maxWidth : $originalWidth;
        $newHeight = (int) round(($originalHeight / max(1, $originalWidth)) * $newWidth);

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        $fileName = sprintf('expense_%s.jpg', bin2hex(random_bytes(8)));
        $targetPath = rtrim($targetDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        imagejpeg($resizedImage, $targetPath, 70);

        if (filesize($targetPath) > 200 * 1024) {
            imagejpeg($resizedImage, $targetPath, 60);
        }

        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        return $fileName;
    }
}
