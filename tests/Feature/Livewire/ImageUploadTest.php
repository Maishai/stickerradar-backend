<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ImageUpload;
use App\Models\Sticker;
use App\Models\Tag;
use App\Services\StickerService;
use App\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_successfully()
    {
        Livewire::test(ImageUpload::class)
            ->assertStatus(200);
    }

    public function test_coordinates_are_extracted_when_image_has_exif_data()
    {
        Http::fake([
            env('CLASSIFIER_URI') => Http::response([
                'sticker_probability' => 0.99,
            ], 200),
        ]);
        $fakeImage = UploadedFile::fake()->image('photo.jpg');

        $mock = Mockery::mock(StickerService::class);
        $mock->shouldReceive('extractCoordinatesFromExif')
            ->once()
            ->with(Mockery::type(TemporaryUploadedFile::class))
            ->andReturn(['lat' => 12.3456, 'lon' => 65.4321]);

        $this->app->instance(StickerService::class, $mock);

        Livewire::test(ImageUpload::class)
            ->set('photo', $fakeImage)
            ->assertSet('lat', 12.3456)
            ->assertSet('lon', 65.4321)
            ->assertSet('noCoordinatesError', false);
    }

    public function test_it_sets_coordinates_when_exif_data_exists()
    {
        Http::fake([
            env('CLASSIFIER_URI') => Http::response([
                'sticker_probability' => 0.99,
            ], 200),
        ]);
        $mock = Mockery::mock(StickerService::class);
        $mock->shouldReceive('extractCoordinatesFromExif')->andReturn(['lat' => 48.123456, 'lon' => 11.654321]);

        $this->app->instance(StickerService::class, $mock);

        $photo = UploadedFile::fake()->image('photo.jpg');

        Livewire::test(ImageUpload::class)
            ->set('photo', $photo)
            ->assertSet('lat', 48.123456)
            ->assertSet('lon', 11.654321)
            ->assertSet('noCoordinatesError', false);
    }

    public function test_no_coordinates_sets_error_flag()
    {
        Http::fake([
            env('CLASSIFIER_URI') => Http::response([
                'sticker_probability' => 0.99,
            ], 200),
        ]);
        $fakeImage = UploadedFile::fake()->image('photo.jpg');

        $mock = Mockery::mock(StickerService::class);
        $mock->shouldReceive('extractCoordinatesFromExif')
            ->once()
            ->andReturn(null);

        $this->app->instance(StickerService::class, $mock);

        Livewire::test(ImageUpload::class)
            ->set('photo', $fakeImage)
            ->assertSet('lat', null)
            ->assertSet('lon', null)
            ->assertSet('noCoordinatesError', true);
    }

    public function test_it_saves_the_sticker_with_valid_data()
    {
        Http::fake([
            env('CLASSIFIER_URI') => Http::response([
                'sticker_probability' => 0.99,
            ], 200),
        ]);
        $mockSticker = new Sticker(['id' => 123]);
        $mock = Mockery::mock(StickerService::class);
        $mock->shouldReceive('extractCoordinatesFromExif')->andReturn(['lat' => 10, 'lon' => 20]);
        $mock->shouldReceive('createSticker')->andReturn($mockSticker);

        $this->app->instance(StickerService::class, $mock);

        $tag = Tag::factory()->create();

        $photo = UploadedFile::fake()->image('photo.jpg');

        Livewire::test(ImageUpload::class)
            ->set('photo', $photo)
            ->set('lat', 10)
            ->set('lon', 20)
            ->set('selectedTags', [$tag->id])
            ->set('selectedState', State::EXISTS)
            ->call('save')
            ->assertRedirect(route('stickers.index'));
    }

    public function test_no_tags_triggers_error()
    {
        $mockSticker = new Sticker(['id' => 123]);
        $mock = Mockery::mock(StickerService::class);
        $mock->shouldReceive('extractCoordinatesFromExif')->andReturn(['lat' => 10, 'lon' => 20]);
        $mock->shouldReceive('createSticker')->andReturn($mockSticker);

        $this->app->instance(StickerService::class, $mock);

        $photo = UploadedFile::fake()->image('photo.jpg');

        Livewire::test(ImageUpload::class)
            ->set('photo', $photo)
            ->set('lat', 10)
            ->set('lon', 20)
            ->set('selectedTags', [])
            ->set('selectedState', State::EXISTS)
            ->call('save')
            ->assertHasErrors('selectedTags');
    }
}
