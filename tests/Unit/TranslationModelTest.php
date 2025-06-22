<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_translation()
    {
        $translation = Translation::create([
            'key' => 'test.key',
            'value' => 'Test Value',
            'locale' => 'en'
        ]);

        $this->assertInstanceOf(Translation::class, $translation);
        $this->assertEquals('test.key', $translation->key);
        $this->assertEquals('Test Value', $translation->value);
        $this->assertEquals('en', $translation->locale);
    }

    /** @test */
    public function it_has_many_to_many_relationship_with_tags()
    {
        $translation = Translation::factory()->create();
        $tag1 = Tag::factory()->create(['name' => 'mobile']);
        $tag2 = Tag::factory()->create(['name' => 'web']);

        $translation->tags()->attach([$tag1->id, $tag2->id]);

        $this->assertCount(2, $translation->tags);
        $this->assertTrue($translation->tags->contains($tag1));
        $this->assertTrue($translation->tags->contains($tag2));
    }

    /** @test */
    public function it_enforces_unique_key_locale_combination()
    {
        Translation::create([
            'key' => 'test.key',
            'value' => 'Test Value',
            'locale' => 'en'
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Translation::create([
            'key' => 'test.key',
            'value' => 'Another Value',
            'locale' => 'en'
        ]);
    }

    /** @test */
    public function it_allows_same_key_with_different_locales()
    {
        $translation1 = Translation::create([
            'key' => 'test.key',
            'value' => 'English Value',
            'locale' => 'en'
        ]);

        $translation2 = Translation::create([
            'key' => 'test.key',
            'value' => 'French Value',
            'locale' => 'fr'
        ]);

        $this->assertNotEquals($translation1->id, $translation2->id);
        $this->assertEquals('test.key', $translation1->key);
        $this->assertEquals('test.key', $translation2->key);
        $this->assertEquals('en', $translation1->locale);
        $this->assertEquals('fr', $translation2->locale);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $translation = new Translation();
        $fillable = $translation->getFillable();

        $this->assertContains('key', $fillable);
        $this->assertContains('value', $fillable);
        $this->assertContains('locale', $fillable);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $translation = Translation::factory()->create();

        $this->assertNotNull($translation->created_at);
        $this->assertNotNull($translation->updated_at);
    }
}
