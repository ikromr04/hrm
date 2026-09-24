<?php

namespace App\Support;

use GdImage;

/**
 * The bits of image handling shared by whatever accepts a photograph: a file
 * straight from a phone is several megabytes and often lying on its side.
 */
class Photo
{
    /**
     * Phones record which way they were held rather than rotating the pixels,
     * so a portrait photo arrives on its side unless the EXIF tag is applied.
     */
    public static function upright(string $path): GdImage
    {
        $image = imagecreatefromstring((string) file_get_contents($path));
        $orientation = @exif_read_data($path)['Orientation'] ?? null;

        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : imagerotate($image, $angle, 0);
    }

    /**
     * The photograph scaled to fit a box, as JPEG, for the copy the interface
     * shows. The upload itself is kept beside it, so the full picture is never
     * lost — only never sent to a list of a hundred rows.
     */
    public static function fit(string $path, int $max, int $quality = 82): string
    {
        $source = self::upright($path);
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $max / max($width, $height));

        $target = imagecreatetruecolor((int) round($width * $scale), (int) round($height * $scale));
        // A transparent PNG would otherwise turn black once flattened to JPEG.
        imagefilledrectangle($target, 0, 0, imagesx($target), imagesy($target), imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);

        ob_start();
        imagejpeg($target, null, $quality);
        $jpeg = (string) ob_get_clean();

        imagedestroy($target);
        imagedestroy($source);

        return $jpeg;
    }
}
