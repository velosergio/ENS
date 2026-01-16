<?php

namespace App\Services;

class ImageService
{
    /**
     * Generar thumbnails desde una imagen en base64.
     *
     * @param  string|null  $base64Image  Imagen en formato base64
     * @return array<string, string|null> Array con los thumbnails: ['50' => base64, '100' => base64, '500' => base64]
     */
    public function generateThumbnails(?string $base64Image): array
    {
        if (! $base64Image) {
            return [
                '50' => null,
                '100' => null,
                '500' => null,
            ];
        }

        // Extraer los datos de la imagen (remover el prefijo data:image/...;base64,)
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
            $imageType = $matches[1];
            $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64Image));
        } else {
            // Si no tiene prefijo, asumir que es solo base64
            $imageData = base64_decode($base64Image);
            $imageType = $this->detectImageType($imageData);
        }

        if (! $imageData) {
            return [
                '50' => null,
                '100' => null,
                '500' => null,
            ];
        }

        // Crear imagen desde string
        $sourceImage = @imagecreatefromstring($imageData);

        if (! $sourceImage) {
            return [
                '50' => null,
                '100' => null,
                '500' => null,
            ];
        }

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        $thumbnails = [];

        // Generar thumbnails para cada tamaño
        foreach ([50, 100, 500] as $size) {
            $thumbnails[(string) $size] = $this->resizeImage(
                $sourceImage,
                $originalWidth,
                $originalHeight,
                $size,
                $imageType,
            );
        }

        imagedestroy($sourceImage);

        return $thumbnails;
    }

    /**
     * Redimensionar una imagen a un tamaño específico con crop centrado para hacerla cuadrada.
     *
     * @param  resource  $sourceImage  Recurso de imagen GD
     * @param  int  $originalWidth  Ancho original
     * @param  int  $originalHeight  Alto original
     * @param  int  $size  Tamaño del thumbnail (será cuadrado: size x size)
     * @param  string  $imageType  Tipo de imagen (jpeg, png, etc.)
     * @return string|null Imagen redimensionada en base64 o null si falla
     */
    protected function resizeImage($sourceImage, int $originalWidth, int $originalHeight, int $size, string $imageType): ?string
    {
        // Calcular dimensiones para crop centrado (siempre cuadrado)
        $newWidth = $size;
        $newHeight = $size;

        // Calcular el tamaño de la fuente para el crop
        // Usamos el lado más pequeño como referencia para mantener la proporción
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

        // Convertir a base64
        ob_start();
        switch ($imageType) {
            case 'png':
                imagepng($thumbnail);
                break;
            case 'gif':
                imagegif($thumbnail);
                break;
            case 'webp':
                imagewebp($thumbnail);
                break;
            default:
                imagejpeg($thumbnail, null, 85);
                break;
        }
        $imageString = ob_get_contents();
        ob_end_clean();

        imagedestroy($thumbnail);

        if (! $imageString) {
            return null;
        }

        $mimeType = match ($imageType) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return 'data:'.$mimeType.';base64,'.base64_encode($imageString);
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
