<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PositionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The employee's photo: a square thumbnail for the interface and the upload
 * itself, kept so the photo can be opened at full size.
 */
class EmployeeAvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PositionSeeder::class]);
        Storage::fake('public');
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    /** The raw column holds a path; the model hands out a URL. */
    private function paths(User $employee): array
    {
        $employee->refresh();

        return [$employee->getRawOriginal('avatar'), $employee->getRawOriginal('avatar_original')];
    }

    public function test_an_admin_uploads_a_photo_and_gets_a_square_thumbnail()
    {
        $employee = User::factory()->create();

        $this->actingAs($this->admin())
            ->post("/employees/{$employee->id}/avatar", [
                // Deliberately not square: the thumbnail must still come out so.
                'avatar' => UploadedFile::fake()->image('photo.jpg', 900, 600),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        [$thumbnail, $original] = $this->paths($employee);
        $this->assertNotNull($thumbnail);
        $this->assertNotNull($original);
        Storage::disk('public')->assertExists([$thumbnail, $original]);

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($thumbnail));
        $this->assertSame([240, 240], [$width, $height]);
    }

    public function test_the_original_is_kept_beside_the_thumbnail()
    {
        $employee = User::factory()->create();

        $this->actingAs($this->admin())
            ->post("/employees/{$employee->id}/avatar", ['avatar' => UploadedFile::fake()->image('photo.jpg', 900, 600)]);

        [$thumbnail, $original] = $this->paths($employee);
        $this->assertNotSame($thumbnail, $original);

        // The upload keeps its own size, so it can be opened properly.
        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($original));
        $this->assertSame([900, 600], [$width, $height]);
    }

    public function test_replacing_a_photo_removes_the_files_it_replaces()
    {
        $employee = User::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/employees/{$employee->id}/avatar", ['avatar' => UploadedFile::fake()->image('first.jpg')]);
        $before = $this->paths($employee);

        $this->actingAs($admin)->post("/employees/{$employee->id}/avatar", ['avatar' => UploadedFile::fake()->image('second.jpg')]);
        $after = $this->paths($employee);

        Storage::disk('public')->assertMissing($before);
        Storage::disk('public')->assertExists($after);
    }

    public function test_an_admin_deletes_the_photo_and_its_files()
    {
        $employee = User::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/employees/{$employee->id}/avatar", ['avatar' => UploadedFile::fake()->image('photo.jpg')]);
        $files = $this->paths($employee);

        $this->actingAs($admin)
            ->delete("/employees/{$employee->id}/avatar")
            ->assertSessionHasNoErrors();

        $this->assertSame([null, null], $this->paths($employee));
        Storage::disk('public')->assertMissing($files);
    }

    public function test_the_model_hands_out_urls_not_paths()
    {
        $employee = User::factory()->create();

        $this->actingAs($this->admin())->post("/employees/{$employee->id}/avatar", ['avatar' => UploadedFile::fake()->image('photo.jpg')]);

        $this->assertStringStartsWith('/storage/', $employee->refresh()->avatar);
        $this->assertStringStartsWith('/storage/', $employee->avatar_original);
    }

    public function test_anything_that_is_not_an_image_is_rejected()
    {
        $employee = User::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post("/employees/{$employee->id}/avatar", ['avatar' => UploadedFile::fake()->create('resume.pdf', 20, 'application/pdf')])
            ->assertSessionHasErrors('avatar');

        // A photo straight from a camera can still be too heavy.
        $this->actingAs($admin)
            ->post("/employees/{$employee->id}/avatar", ['avatar' => UploadedFile::fake()->image('huge.jpg')->size(9000)])
            ->assertSessionHasErrors('avatar');

        $this->assertSame([null, null], $this->paths($employee));
    }

    public function test_an_employee_cannot_change_anyones_photo()
    {
        $employee = User::factory()->create();

        // Not even their own: the photo is managed by HR.
        $this->actingAs($employee);
        $this->post("/employees/{$employee->id}/avatar", ['avatar' => UploadedFile::fake()->image('photo.jpg')])->assertForbidden();
        $this->delete("/employees/{$employee->id}/avatar")->assertForbidden();
    }
}
