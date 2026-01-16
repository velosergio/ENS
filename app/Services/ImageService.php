<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Guardar imagen desde archivo y generar thumbnails.
     *
     * @param  \Illuminate\Http\UploadedFile|null  $file  Archivo de imagen
     * @param  string  $folder  Carpeta donde guardar (ej: 'parejas', 'users')
     * @param  string|null  $oldPath  Path de la imagen anterior para eliminarla
     * @return array<string, string|null> Array con paths: ['original' => path, '50' => path, '100' => path, '500' => path]
     */
    public function saveImageFromFile(?\Illuminate\Http\UploadedFile $file, string $folder = 'images', ?string $oldPath = null): array
    {
        if (! $file || ! $file->isValid()) {
            // Si hay una imagen anterior, mantenerla
            if ($oldPath) {
                $oldThumbnails = $this->getThumbnailPaths($oldPath);

                return [
                    'original' => $oldPath,
                    '50' => $oldThumbnails['50'],
                    '100' => $oldThumbnails['100'],
                    '500' => $oldThumbnails['500'],
                ];
            }

            return [
                'original' => null,
                '50' => null,
                '100' => null,
                '500' => null,
            ];
        }

        // Eliminar imagen anterior si existe
        if ($oldPath) {
            $this->deleteImage($oldPath);
        }

        // Leer datos del archivo
        $imageData = file_get_contents($file->getRealPath());
        $imageType = $this->detectImageType($imageData);

        // Crear imagen desde string
        $sourceImage = @imagecreatefromstring($imageData);

        if (! $sourceImage) {
            return [
                'original' => null,
                '50' => null,
                '100' => null,
                '500' => null,
            ];
        }

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        // Generar nombre único para la imagen
        $filename = Str::uuid().'.'.$imageType;
        $path = $folder.'/'.$filename;

        // Guardar imagen original
        $this->saveImageFile($sourceImage, $imageType, $path);

        // Generar y guardar thumbnails
        $thumbnails = [];
        foreach ([50, 100, 500] as $size) {
            $thumbnailPath = $this->generateThumbnail($sourceImage, $originalWidth, $originalHeight, $size, $imageType, $folder, $filename);
            $thumbnails[(string) $size] = $thumbnailPath;
        }

        imagedestroy($sourceImage);

        return [
            'original' => $path,
            '50' => $thumbnails['50'],
            '100' => $thumbnails['100'],
            '500' => $thumbnails['500'],
        ];
    }

    /**
     * Guardar imagen desde base64 y generar thumbnails.
     *
     * @param  string|null  $base64Image  Imagen en formato base64
     * @param  string  $folder  Carpeta donde guardar (ej: 'parejas', 'users')
     * @param  string|null  $oldPath  Path de la imagen anterior para eliminarla
     * @return array<string, string|null> Array con paths: ['original' => path, '50' => path, '100' => path, '500' => path]
     */
    public function saveImageFromBase64(?string $base64Image, string $folder = 'images', ?string $oldPath = null): array
    {
        if (! $base64Image) {
            // Si hay una imagen anterior, mantenerla
            if ($oldPath) {
                $oldThumbnails = $this->getThumbnailPaths($oldPath);

                return [
                    'original' => $oldPath,
                    '50' => $oldThumbnails['50'],
                    '100' => $oldThumbnails['100'],
                    '500' => $oldThumbnails['500'],
                ];
            }

            return [
                'original' => null,
                '50' => null,
                '100' => null,
                '500' => null,
            ];
        }

        // Eliminar imagen anterior si existe
        if ($oldPath) {
            $this->deleteImage($oldPath);
        }

        // Extraer los datos de la imagen
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
            $imageType = $matches[1];
            $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64Image));
        } else {
            $imageData = base64_decode($base64Image);
            $imageType = $this->detectImageType($imageData);
        }

        if (! $imageData) {
            return [
                'original' => null,
                '50' => null,
                '100' => null,
                '500' => null,
            ];
        }

        // Crear imagen desde string
        $sourceImage = @imagecreatefromstring($imageData);

        if (! $sourceImage) {
            return [
                'original' => null,
                '50' => null,
                '100' => null,
                '500' => null,
            ];
        }

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        // Generar nombre único para la imagen
        $filename = Str::uuid().'.'.$imageType;
        $path = $folder.'/'.$filename;

        // Guardar imagen original
        $this->saveImageFile($sourceImage, $imageType, $path);

        // Generar y guardar thumbnails
        $thumbnails = [];
        foreach ([50, 100, 500] as $size) {
            $thumbnailPath = $this->generateThumbnail($sourceImage, $originalWidth, $originalHeight, $size, $imageType, $folder, $filename);
            $thumbnails[(string) $size] = $thumbnailPath;
        }

        imagedestroy($sourceImage);

        return [
            'original' => $path,
            '50' => $thumbnails['50'],
            '100' => $thumbnails['100'],
            '500' => $thumbnails['500'],
        ];
    }

    /**
     * Generar thumbnail y guardarlo como archivo.
     *
     * @param  resource  $sourceImage  Recurso de imagen GD
     * @param  int  $originalWidth  Ancho original
     * @param  int  $originalHeight  Alto original
     * @param  int  $size  Tamaño del thumbnail (será cuadrado: size x size)
     * @param  string  $imageType  Tipo de imagen (jpeg, png, etc.)
     * @param  string  $folder  Carpeta donde guardar
     * @param  string  $originalFilename  Nombre del archivo original
     * @return string|null Path del thumbnail guardado o null si falla
     */
    protected function generateThumbnail($sourceImage, int $originalWidth, int $originalHeight, int $size, string $imageType, string $folder, string $originalFilename): ?string
    {
        // Calcular dimensiones para crop centrado (siempre cuadrado)
        $newWidth = $size;
        $newHeight = $size;

        // Calcular el tamaño de la fuente para el crop
        $ratio = min($originalWidth / $size, $originalHeight / $size);
        $sourceWidth = (int) ($size * $ratio);
        $sourceHeight = (int) ($size * $ratio);

        // Calcular el punto de inicio para el crop centrado
        $sourceX = (int) (($originalWidth - $sourceWidth) / 2);
        $sourceY = (int) (($originalHeight - $sourceHeight) / 2);

        // Crear imagen redimensionada (siempre cuadrada)
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

        // Preservar transparencia para PNG
        if ($imageType === 'png') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Redimensionar con crop centrado
        imagecopyresampled(
            $thumbnail,
            $sourceImage,
            0,
            0,
            $sourceX,
            $sourceY,
            $newWidth,
            $newHeight,
            $sourceWidth,
            $sourceHeight,
        );

        // Generar nombre del thumbnail
        $nameWithoutExt = pathinfo($originalFilename, PATHINFO_FILENAME);
        $thumbnailFilename = $nameWithoutExt.'_'.$size.'.'.$imageType;
        $thumbnailPath = $folder.'/thumbnails/'.$thumbnailFilename;

        // Guardar thumbnail
        $this->saveImageFile($thumbnail, $imageType, $thumbnailPath);

        imagedestroy($thumbnail);

        return $thumbnailPath;
    }

    /**
     * Guardar imagen como archivo en storage.
     *
     * @param  resource  $imageResource  Recurso de imagen GD
     * @param  string  $imageType  Tipo de imagen (jpeg, png, etc.)
     * @param  string  $path  Path donde guardar
     */
    protected function saveImageFile($imageResource, string $imageType, string $path): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'img_');
        $tempHandle = fopen($tempFile, 'w');

        switch ($imageType) {
            case 'png':
                imagepng($imageResource, $tempFile);
                break;
            case 'gif':
                imagegif($imageResource, $tempFile);
                break;
            case 'webp':
                imagewebp($imageResource, $tempFile);
                break;
            default:
                imagejpeg($imageResource, $tempFile, 85);
                break;
        }

        fclose($tempHandle);

        // Guardar en storage público
        Storage::disk('public')->put($path, file_get_contents($tempFile));

        // Eliminar archivo temporal
        @unlink($tempFile);
    }

    /**
     * Obtener URL pública de una imagen.
     *
     * @param  string|null  $path  Path de la imagen
     * @return string|null URL pública o null si no hay path
     */
    public function getImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Obtener URLs de thumbnails desde el path original.
     *
     * @param  string|null  $originalPath  Path de la imagen original
     * @return array<string, string|null> Array con URLs: ['50' => url, '100' => url, '500' => url]
     */
    public function getThumbnailUrls(?string $originalPath): array
    {
        if (! $originalPath) {
            return [
                '50' => null,
                '100' => null,
                '500' => null,
            ];
        }

        $paths = $this->getThumbnailPaths($originalPath);

        return [
            '50' => $this->getImageUrl($paths['50']),
            '100' => $this->getImageUrl($paths['100']),
            '500' => $this->getImageUrl($paths['500']),
        ];
    }

    /**
     * Obtener paths de thumbnails desde el path original.
     *
     * @param  string  $originalPath  Path de la imagen original
     * @return array<string, string|null> Array con paths: ['50' => path, '100' => path, '500' => path]
     */
    protected function getThumbnailPaths(string $originalPath): array
    {
        $pathInfo = pathinfo($originalPath);
        $folder = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? 'jpg';

        return [
            '50' => $folder.'/thumbnails/'.$filename.'_50.'.$extension,
            '100' => $folder.'/thumbnails/'.$filename.'_100.'.$extension,
            '500' => $folder.'/thumbnails/'.$filename.'_500.'.$extension,
        ];
    }

    /**
     * Eliminar imagen y sus thumbnails.
     *
     * @param  string|null  $path  Path de la imagen a eliminar
     */
    public function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        // Eliminar imagen original
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        // Eliminar thumbnails
        $thumbnailPaths = $this->getThumbnailPaths($path);
        foreach ($thumbnailPaths as $thumbnailPath) {
            if (Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
        }
    }

    /**
     * Detectar el tipo de imagen desde los datos binarios.
     *
     * @param  string  $imageData  Datos binarios de la imagen
     * @return string Tipo de imagen (jpeg, png, gif, webp)
     */
    protected function detectImageType(string $imageData): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);

        return match ($mimeType) {
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpeg',
        };
    }
}
