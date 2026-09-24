<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Photo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The employee's photo. Two files are kept: the square thumbnail the interface
 * shows everywhere, and the upload itself, so the photo can be opened full size.
 */
class EmployeeAvatarController extends Controller
{
    /** The interface never shows an avatar larger than this. */
    private const THUMBNAIL = 240;

    public function update(Request $request, User $employee): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:8192'],
        ], attributes: ['avatar' => 'фотография']);

        /** @var UploadedFile $file */
        $file = $request->file('avatar');
        $folder = "avatars/{$employee->id}";
        $name = Str::random(20);

        $original = $file->storeAs($folder, "{$name}.".$file->extension(), 'public');
        $thumbnail = "{$folder}/{$name}_".self::THUMBNAIL.'.jpg';
        Storage::disk('public')->put($thumbnail, $this->square(Storage::disk('public')->path($original)));

        $previous = [$employee->getRawOriginal('avatar'), $employee->getRawOriginal('avatar_original')];

        DB::transaction(fn () => $employee->update(['avatar' => $thumbnail, 'avatar_original' => $original]));

        $this->forget($previous);

        return back();
    }

    public function destroy(User $employee): RedirectResponse
    {
        $files = [$employee->getRawOriginal('avatar'), $employee->getRawOriginal('avatar_original')];

        $employee->update(['avatar' => null, 'avatar_original' => null]);

        $this->forget($files);

        return back();
    }

    /**
     * @param  array<int, string|null>  $paths
     */
    private function forget(array $paths): void
    {
        // Only after the row no longer points at them, so a failed save never
        // leaves the employee with a path to a file that is gone.
        Storage::disk('public')->delete(array_filter($paths));
    }

    /**
     * The upload cropped to a centred square and scaled down, as JPEG. A photo
     * straight from a phone is several megabytes; a list of a hundred
     * colleagues would carry every one of them.
     */
    private function square(string $path): string
    {
        $source = Photo::upright($path);
        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $canvas = imagecreatetruecolor(self::THUMBNAIL, self::THUMBNAIL);
        // A transparent PNG would otherwise turn black once flattened to JPEG.
        imagefilledrectangle($canvas, 0, 0, self::THUMBNAIL, self::THUMBNAIL, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled(
            $canvas, $source,
            0, 0,
            intdiv($width - $side, 2), intdiv($height - $side, 2),
            self::THUMBNAIL, self::THUMBNAIL,
            $side, $side,
        );

        ob_start();
        imagejpeg($canvas, null, 82);
        $jpeg = (string) ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($source);

        return $jpeg;
    }
}
