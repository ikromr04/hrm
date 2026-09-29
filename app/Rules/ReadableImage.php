<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * A picture the application can actually open.
 *
 * Everything that accepts a photograph goes on to cut a smaller copy of it with
 * GD, and GD reads a shorter list of formats than a browser will happily hand
 * over. Checked at the door, where a person still sees a form with a message on
 * it, rather than halfway through saving, where they would see a blank error
 * page instead.
 */
class ReadableImage implements ValidationRule
{
    /**
     * What to say when a file is not a photograph that can be opened. The
     * commonest case by far is a picture straight off an iPhone: the phone
     * records HEIC, the browser hands it over without a word, and neither
     * Laravel nor GD reads it — so the message says what to do about it rather
     * than that something is wrong. Shared with the rules that check the format
     * by name, because to the person it is all one answer.
     */
    public const MESSAGE = 'Фото должно быть в JPEG, PNG или WebP. Снимки с iPhone бывают в формате HEIC — сохраните такое фото как JPEG.';

    /**
     * What GD can open, by the type getimagesize() reports.
     *
     * @var array<int, int>
     */
    private const SUPPORTED = [
        IMAGETYPE_JPEG => IMG_JPG,
        IMAGETYPE_PNG => IMG_PNG,
        IMAGETYPE_GIF => IMG_GIF,
        IMAGETYPE_WEBP => IMG_WEBP,
        IMAGETYPE_BMP => IMG_BMP,
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Not a file at all, or one refused already: the other rules say so.
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        // getimagesize rather than a full decode: it reads the header, says what
        // the file really is whatever it is called, and costs no memory for a
        // twelve-megabyte photograph.
        $info = @getimagesize($value->getRealPath());

        if ($info === false || ! isset(self::SUPPORTED[$info[2]]) || (imagetypes() & self::SUPPORTED[$info[2]]) === 0) {
            $fail(self::MESSAGE);
        }
    }
}
