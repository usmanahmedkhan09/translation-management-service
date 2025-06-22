<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TranslationApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $tags;
    protected $translations;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->createTestData();
    }

    private function createTestData()
    {
        // Create tags
        $this->tags = Tag::factory()->count(5)->create([
            'name' => fn() => fake()->unique()->randomElement(['mobile', 'desktop', 'web', 'admin', 'public'])
        ]);
        
        // Create translations
        $this->translations = Translation::factory()->count(20)->create();
        
        // Attach tags to some translations
        $this->translations->each(function ($translation) {
            $randomTags = $this->tags->random(rand(0, 3));
            $translation->tags()->attach($randomTags);
        });
    }

    /** @test */
    public function can_get_all_translations()
    {
        $response = $this->getJson('/api/translations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'key',
                        'value',
                        'locale',
                        'created_at',
                        'updated_at',
                        'tags'
                    ]
                ],
                'current_page',
                'per_page',
                'total'
            ]);
    }

    /** @test */
    public function can_get_translation_by_id()
    {
        $translation = Translation::first();

        $response = $this->getJson("/api/translations/{$translation->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $translation->id,
                'key' => $translation->key,
                'value' => $translation->value,
                'locale' => $translation->locale
            ]);
    }

    /** @test */
    public function can_create_translation()
    {
        $data = [
            'key' => 'test.key',
            'value' => 'Test Value',
            'locale' => 'en'
        ];

        $response = $this->postJson('/api/translations', $data);

        $response->assertStatus(201)
            ->assertJson([
                'key' => 'test.key',
                'value' => 'Test Value',
                'locale' => 'en'
            ]);

        $this->assertDatabaseHas('translations', $data);
    }

    /** @test */
    public function can_create_translation_with_tags()
    {
        $data = [
            'key' => 'test.with.tags',
            'value' => 'Test With Tags',
            'locale' => 'fr',
            'tags' => ['test-tag-unique']
        ];

        $response = $this->postJson('/api/translations', $data);

        $response->assertStatus(201)
            ->assertJson([
                'key' => 'test.with.tags',
                'value' => 'Test With Tags',
                'locale' => 'fr',
                'tags' => [
                    [
                        'name' => 'test-tag-unique'
                    ]
                ]
            ]);
    }

    /** @test */
    public function validates_required_fields()
    {
        $response = $this->postJson('/api/translations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key', 'value', 'locale']);
    }

    /** @test */
    public function validates_unique_key_locale_combination()
    {
        $translation = Translation::first();
        
        $data = [
            'key' => $translation->key,
            'value' => 'Different Value',
            'locale' => $translation->locale
        ];

        $response = $this->postJson('/api/translations', $data);

        $response->assertStatus(409)
            ->assertJson(['error' => 'Translation with this key and locale already exists']);
    }

    /** @test */
    public function can_update_translation()
    {
        $translation = $this->translations->first();
        
        $data = [
            'value' => 'Updated Value',
            'locale' => 'fr'
        ];

        $response = $this->putJson("/api/translations/{$translation->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $translation->id,
                'value' => 'Updated Value',
                'locale' => 'fr'
            ]);
    }

    /** @test */
    public function returns_404_for_nonexistent_translation()
    {
        $response = $this->getJson('/api/translations/999999');

        $response->assertStatus(404)
            ->assertJson(['error' => 'Translation not found']);
    }

    /** @test */
    public function can_delete_translation()
    {
        $translation = Translation::first();

        $response = $this->deleteJson("/api/translations/{$translation->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Translation deleted successfully']);
        
        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    }

    /** @test */
    public function can_search_translations_by_key()
    {
        $translation = Translation::first();
        $searchKey = substr($translation->key, 0, 5);

        $response = $this->getJson("/api/translations?key={$searchKey}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        
        foreach ($data as $item) {
            $this->assertStringContainsString($searchKey, $item['key']);
        }
    }

    /** @test */
    public function can_search_translations_by_content()
    {
        $translation = Translation::first();
        $searchContent = trim(substr($translation->value, 0, 5));

        $response = $this->getJson("/api/translations?content=" . urlencode($searchContent));

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    /** @test */
    public function can_filter_translations_by_locale()
    {
        $locale = Translation::first()->locale;

        $response = $this->getJson("/api/translations?locale={$locale}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals($locale, $item['locale']);
        }
    }

    /** @test */
    public function can_filter_translations_by_tags()
    {
        $tag = $this->tags->first();

        $response = $this->getJson("/api/translations?tags[]={$tag->name}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    /** @test */
    public function can_export_translations_for_locale()
    {
        $locale = Translation::first()->locale;

        $response = $this->getJson("/api/translations/export?locale={$locale}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'locale',
                'translations',
                'count',
                'generated_at'
            ]);

        $data = $response->json();
        $this->assertEquals($locale, $data['locale']);
        $this->assertIsArray($data['translations']);
    }

    /** @test */
    public function can_get_available_locales()
    {
        $response = $this->getJson('/api/translations/locales');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'locales' => []
            ]);

        $locales = $response->json('locales');
        $this->assertIsArray($locales);
        $this->assertNotEmpty($locales);
    }

    /** @test */
    public function can_get_available_tags()
    {
        $response = $this->getJson('/api/translations/tags');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tags' => []
            ]);

        $tags = $response->json('tags');
        $this->assertIsArray($tags);
        $this->assertNotEmpty($tags);
    }

    /** @test */
    public function cache_is_cleared_when_translation_is_created()
    {
        // First request to populate cache
        $response = $this->getJson('/api/translations');
        $initialTotal = $response->json('total');

        // Create new translation
        $createResponse = $this->postJson('/api/translations', [
            'key' => 'cache.test.unique',
            'value' => 'Cache Test',
            'locale' => 'en'
        ]);
        
        $createResponse->assertStatus(201);

        // New request should include the new translation
        $response = $this->getJson('/api/translations');
        $newTotal = $response->json('total');
        
        $this->assertGreaterThan($initialTotal, $newTotal);
        
        // Verify the new translation is actually there by searching for it
        $searchResponse = $this->getJson('/api/translations?key=cache.test.unique');
        $searchData = $searchResponse->json('data');
        $this->assertNotEmpty($searchData);
        $this->assertEquals('cache.test.unique', $searchData[0]['key']);
    }

    /** @test */
    public function handles_large_dataset_pagination()
    {
        // Create additional translations
        Translation::factory()->count(100)->create();

        $response = $this->getJson('/api/translations?per_page=50');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
                'last_page'
            ]);

        $this->assertEquals(50, $response->json('per_page'));
        $this->assertCount(50, $response->json('data'));
    }

    /** @test */
    public function respects_per_page_limit()
    {
        $response = $this->getJson('/api/translations?per_page=200');

        $response->assertStatus(200);
        
        // Should be capped at 100
        $this->assertLessThanOrEqual(100, $response->json('per_page'));
    }

    /** @test */
    public function handles_concurrent_requests()
    {
        $promises = [];
        
        // Simulate concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $promises[] = $this->getJson('/api/translations');
        }

        foreach ($promises as $response) {
            $response->assertStatus(200);
        }
    }
}
