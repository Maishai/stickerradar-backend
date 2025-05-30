<?php

namespace Tests\Feature;

use App\Models\Sticker;
use App\Models\Tag;
use App\Services\StickerService;
use App\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StickerApiControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stickerService = $this->app->make(StickerService::class);
        Storage::fake('public');
    }

    public function test_index_filter_by_latitude_returns_correct_stickers()
    {
        Sticker::factory()->create(['lat' => 10, 'lon' => 10]);
        Sticker::factory()->create(['lat' => 20, 'lon' => 20]);
        Sticker::factory()->create(['lat' => 30, 'lon' => 30]);

        $this->travel(10)->minutes();

        $response = $this->getJson(route('api.stickers.index', ['min_lat' => 15, 'max_lat' => 25, 'min_lon' => 15, 'max_lon' => 25]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['lat' => 20, 'lon' => 20]);
    }

    public function test_index_doesnt_return_stickers_younger_than_ten_minutes()
    {

        $oldest = Sticker::factory()->create(['lat' => 20, 'lon' => 20]);

        $this->travel(3)->minutes();

        Sticker::factory()->create(['lat' => 20, 'lon' => 20]);

        $this->travel(3)->minutes();

        Sticker::factory()->create(['lat' => 20, 'lon' => 20]);

        $this->travel(4)->minutes();

        $response = $this->getJson(route('api.stickers.index', ['min_lat' => 15, 'max_lat' => 25, 'min_lon' => 15, 'max_lon' => 25]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $oldest->id]);
    }

    public function test_store_valid_data_creates_and_returns_sticker()
    {
        $tags = Tag::factory()->count(2)->create();

        $base64Image = base64_encode(file_get_contents('storage/example-images/fussball.jpeg'));

        $this->freezeTime();
        $response = $this->postJson(route('api.stickers.store'), [
            'lat' => 40.7128,
            'lon' => -74.0060,
            'image' => 'data:image/jpeg;base64,'.$base64Image,
            'tags' => $tags->pluck('id')->toArray(),
            'state' => State::EXISTS->value,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'lat',
                    'lon',
                    'filename',
                    'state',
                    'last_seen',
                    'tags',
                ],
            ])
            ->assertJson([
                'data' => [
                    'lat' => 40.7128,
                    'lon' => -74.0060,
                    'state' => State::EXISTS->value,
                    'tags' => $tags->pluck('id')->toArray(),
                ],
            ]);

        $this->assertDatabaseHas('stickers', [
            'lat' => 40.7128,
            'lon' => -74.0060,
        ]);

        $this->assertDatabaseHas('state_histories', [
            'sticker_id' => Sticker::latest()->first()->id,
            'state' => State::EXISTS->value,
            'last_seen' => now(),
        ]);

        foreach ($tags as $tag) {
            $this->assertDatabaseHas('sticker_tag', [
                'sticker_id' => Sticker::latest()->first()->id,
                'tag_id' => $tag->id,
            ]);
        }

        $filename = Sticker::first()->filename;
        Storage::disk('public')->assertExists(["stickers/$filename", "stickers/thumbnails/$filename"]);
    }

    public function test_store_valid_data_without_state_creates_and_returns_sticker_with_default_state()
    {
        $tags = Tag::factory()->count(2)->create();

        $base64Image = base64_encode(file_get_contents('storage/example-images/fussball.jpeg'));

        $this->freezeTime();
        $response = $this->postJson(route('api.stickers.store'), [
            'lat' => 40.7128,
            'lon' => -74.0060,
            'image' => 'data:image/jpeg;base64,'.$base64Image,
            'tags' => $tags->pluck('id')->toArray(),
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'lat' => 40.7128,
                    'lon' => -74.0060,
                    'state' => State::EXISTS->value,
                    'tags' => $tags->pluck('id')->toArray(),
                ],
            ]);

        $this->assertDatabaseHas('state_histories', [
            'sticker_id' => Sticker::latest()->first()->id,
            'state' => State::EXISTS->value,
            'last_seen' => now(),
        ]);

        $this->assertDatabaseHas('stickers', [
            'lat' => 40.7128,
            'lon' => -74.0060,
        ]);

        $filename = Sticker::first()->filename;
        Storage::disk('public')->assertExists(["stickers/$filename", "stickers/thumbnails/$filename"]);
    }

    public function test_store_invalid_latitude_returns_validation_error()
    {
        $base64Image = base64_encode(file_get_contents('storage/example-images/fussball.jpeg'));

        $response = $this->postJson(route('api.stickers.store'), [
            'lat' => 100,
            'lon' => -74.0060,
            'image' => 'data:image/jpeg;base64,'.$base64Image,
            'tags' => [1],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lat']);
    }

    public function test_store_invalid_longitude_returns_validation_error()
    {
        $base64Image = base64_encode(file_get_contents('storage/example-images/fussball.jpeg'));

        $response = $this->postJson(route('api.stickers.store'), [
            'lat' => 40.7128,
            'lon' => 200,
            'image' => 'data:image/jpeg;base64,'.$base64Image,
            'tags' => [1],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lon']);
    }

    public function test_store_missing_image_returns_validation_error()
    {
        $response = $this->postJson(route('api.stickers.store'), [
            'lat' => 40.7128,
            'lon' => -74.0060,
            'tags' => [1],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_store_invalid_image_type_returns_validation_error()
    {
        $response = $this->postJson(route('api.stickers.store'), [
            'lat' => 40.7128,
            'lon' => -74.0060,
            'image' => 'data:application/pdf;base64,bla',
            'tags' => [1],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_store_missing_tags_returns_validation_error()
    {
        $base64Image = base64_encode(file_get_contents('storage/example-images/fussball.jpeg'));

        $response = $this->postJson(route('api.stickers.store'), [
            'lat' => 40.7128,
            'lon' => -74.0060,
            'image' => 'data:image/jpeg;base64,'.$base64Image,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
    }

    public function test_store_invalid_tag_id_returns_validation_error()
    {
        $base64Image = base64_encode(file_get_contents('storage/example-images/fussball.jpeg'));

        $response = $this->postJson(route('api.stickers.store'), [
            'lat' => 40.7128,
            'lon' => -74.0060,
            'image' => 'data:image/jpeg;base64,'.$base64Image,
            'tags' => [999],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags.0']);
    }

    public function test_store_invalid_state_enum_value_returns_validation_error()
    {
        $base64Image = base64_encode(file_get_contents('storage/example-images/fussball.jpeg'));

        $response = $this->postJson(route('api.stickers.store'), [
            'lat' => 40.7128,
            'lon' => -74.0060,
            'image' => 'data:image/jpeg;base64,'.$base64Image,
            'tags' => Tag::factory()->create()->pluck('id')->toArray(),
            'state' => 'invalid_state',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['state']);
    }

    public function test_store_invalid_tags_has_error()
    {
        $base64Image = base64_encode(file_get_contents('storage/example-images/fussball.jpeg'));
        $parent = Tag::factory()->create();
        $child = Tag::factory(['super_tag' => $parent->id])->create();

        $response = $this->postJson(route('api.stickers.store'), [
            'lat' => 40.7128,
            'lon' => -74.0060,
            'image' => 'data:image/jpeg;base64,'.$base64Image,
            'tags' => [$parent->id, $child->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
    }

    public function test_update_with_with_valid_data()
    {
        $uncertainTag = Tag::factory()->create(['name' => 'Ich weiß es nicht']);
        $sticker = Sticker::factory()->create();
        $sticker->tags()->attach($uncertainTag);
        $tag = Tag::factory()->create();

        $response = $this->putJson(route('api.stickers.update', $sticker->id), [
            'tags' => [$tag->id],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('sticker_tag', [
            'sticker_id' => $sticker->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseMissing('sticker_tag', [
            'sticker_id' => $sticker->id,
            'tag_id' => $uncertainTag->id,
        ]);
    }

    public function test_update_without_uncertain_tag_should_fail()
    {
        $oldTag = Tag::factory()->create();
        $sticker = Sticker::factory()->create();
        $sticker->tags()->attach($oldTag);
        $tag = Tag::factory()->create();

        $response = $this->putJson(route('api.stickers.update', $sticker->id), [
            'tags' => [$tag->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
        $this->assertDatabaseHas('sticker_tag', [
            'sticker_id' => $sticker->id,
            'tag_id' => $oldTag->id,
        ]);
    }

    public function test_update_with_tag_and_super_tag_should_fail()
    {
        $uncertainTag = Tag::factory()->create(['name' => 'Ich weiß es nicht']);
        $sticker = Sticker::factory()->create();
        $sticker->tags()->attach($uncertainTag);
        $parentTag = Tag::factory()->create();
        $childTag = Tag::factory()->withSpecificSuperTag($parentTag->id)->create();

        $response = $this->putJson(route('api.stickers.update', $sticker->id), [
            'tags' => [$parentTag->id, $childTag->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
        $this->assertDatabaseHas('sticker_tag', [
            'sticker_id' => $sticker->id,
            'tag_id' => $uncertainTag->id,
        ]);
    }

    public function test_show_existing_sticker_returns_sticker()
    {

        $sticker = Sticker::factory()->has(Tag::factory()->count(2))->create();

        $response = $this->getJson(route('api.stickers.show', $sticker->id));

        $response->assertOk()

            ->assertJsonStructure([

                'data' => [

                    'id',

                    'lat',

                    'lon',

                    'filename',

                    'state',

                    'tags',

                ],

            ])

            ->assertJson([

                'data' => [

                    'id' => $sticker->id,

                    'lat' => $sticker->lat,

                    'lon' => $sticker->lon,

                    'state' => $sticker->latestStateHistory->state->value,

                    'last_seen' => $sticker->latestStateHistory->last_seen,

                    'filename' => $sticker->filename,

                    'tags' => $sticker->tags->pluck('id')->toArray(),

                ],

            ]);

    }

    public function test_show_non_existing_sticker_returns_not_found()
    {

        $response = $this->getJson(route('api.stickers.show', ['sticker' => 'non-existent-uuid']));

        $response->assertNotFound();

    }
}
