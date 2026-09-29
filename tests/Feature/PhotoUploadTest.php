<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\User;
use App\Rules\ReadableImage;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * What may be handed in as a photograph.
 *
 * Everything that takes a picture cuts a smaller copy of it with GD, and GD
 * reads fewer formats than a browser will offer. A phone is the usual source of
 * trouble: an iPhone records HEIC, hands it over without a word, and nothing in
 * the chain can open it — so it is refused at the door, with a message that says
 * what to do instead of that something went wrong.
 */
class PhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('public');
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function unit(): Equipment
    {
        return Equipment::factory()->create(['equipment_type_id' => EquipmentType::create(['name' => 'Ноутбуки'])->id]);
    }

    public function test_a_photograph_from_an_iphone_is_refused_with_a_message_that_helps()
    {
        $unit = $this->unit();

        $this->actingAs($this->admin())
            ->put("/equipment/{$unit->id}/state", [
                'condition' => 'Рабочее',
                'photos' => [UploadedFile::fake()->create('IMG_0421.heic', 600, 'image/heic')],
            ])
            ->assertSessionHasErrors(['photos.0' => ReadableImage::MESSAGE]);

        $this->assertSame(0, $unit->events()->where('kind', 'condition')->count());
    }

    public function test_a_file_that_only_calls_itself_a_photograph_is_refused_rather_than_breaking_the_save()
    {
        $unit = $this->unit();

        // Passes the format rules — it is called .jpg and says image/jpeg — and
        // has nothing inside that GD could open. Before the check at the door
        // this reached the resizing and answered with a blank error page.
        $this->actingAs($this->admin())
            ->put("/equipment/{$unit->id}/state", [
                'condition' => 'Рабочее',
                'photos' => [UploadedFile::fake()->create('IMG_0421.jpg', 20, 'image/jpeg')],
            ])
            ->assertSessionHasErrors(['photos.0' => ReadableImage::MESSAGE]);
    }

    public function test_a_real_photograph_is_taken_and_kept_with_its_preview()
    {
        $unit = $this->unit();

        $this->actingAs($this->admin())
            ->put("/equipment/{$unit->id}/state", [
                'condition' => 'Рабочее, следы эксплуатации',
                'photos' => [UploadedFile::fake()->image('IMG_0421.jpg', 1200, 900)],
            ])
            ->assertSessionHasNoErrors();

        $photo = $unit->events()->where('kind', 'condition')->sole()->photos()->sole();

        Storage::disk('public')->assertExists($photo->path);
        Storage::disk('public')->assertExists($photo->preview);
    }

    public function test_an_avatar_goes_by_the_same_rules()
    {
        $employee = User::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post("/employees/{$employee->id}/avatar", ['avatar' => UploadedFile::fake()->create('me.heic', 600, 'image/heic')])
            ->assertSessionHasErrors(['avatar' => ReadableImage::MESSAGE]);

        $this->assertNull($employee->fresh()->getRawOriginal('avatar'));

        $this->actingAs($admin)
            ->post("/employees/{$employee->id}/avatar", ['avatar' => UploadedFile::fake()->image('me.jpg', 800, 800)])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($employee->fresh()->getRawOriginal('avatar'));
    }
}
