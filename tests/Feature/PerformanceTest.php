<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $authHeaders;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create authenticated user
        $this->user = User::factory()->create();
        $token = $this->user->createToken('test_token')->plainTextToken;
        $this->authHeaders = ['Authorization' => 'Bearer ' . $token];
        
        // Create test data for performance testing
        $this->createPerformanceTestData();
    }

    private function createPerformanceTestData()
    {
        // Create tags
        $tags = Tag::factory()->count(5)->create();
        
        // Create translations
        $translations = Translation::factory()->count(1000)->create();
        
        // Attach tags to translations
        $translations->each(function ($translation) use ($tags) {
            $translation->tags()->attach($tags->random(2)->pluck('id'));
        });
    }

    /** @test */
    public function translations_index_responds_within_200ms()
    {
        $startTime = microtime(true);
        
        $response = $this->withHeaders($this->authHeaders)->getJson('/api/translations?per_page=20');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime, "Response time was {$responseTime}ms, should be < 200ms");
    }

    /** @test */
    public function translations_search_responds_within_200ms()
    {
        $tag = Tag::first();
        
        $startTime = microtime(true);
        
        $response = $this->withHeaders($this->authHeaders)->getJson("/api/search/translations?tags={$tag->name}&per_page=20");
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime, "Search response time was {$responseTime}ms, should be < 200ms");
    }

    /** @test */
    public function translations_show_responds_within_200ms()
    {
        $translation = Translation::first();
        
        $startTime = microtime(true);
        
        $response = $this->withHeaders($this->authHeaders)->getJson("/api/translations/{$translation->id}");
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime, "Show response time was {$responseTime}ms, should be < 200ms");
    }

    /** @test */
    public function translations_create_responds_within_200ms()
    {
        $data = [
            'key' => 'performance.test',
            'value' => 'Performance Test Value',
            'locale' => 'en',
            'tags' => ['mobile']
        ];
        
        $startTime = microtime(true);
        
        $response = $this->withHeaders($this->authHeaders)->postJson('/api/translations', $data);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(201);
        $this->assertLessThan(200, $responseTime, "Create response time was {$responseTime}ms, should be < 200ms");
    }

    /** @test */
    public function translations_update_responds_within_200ms()
    {
        $translation = Translation::first();
        $data = [
            'value' => 'Updated Performance Test Value'
        ];
        
        $startTime = microtime(true);
        
        $response = $this->withHeaders($this->authHeaders)->putJson("/api/translations/{$translation->id}", $data);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime, "Update response time was {$responseTime}ms, should be < 200ms");
    }

    /** @test */
    public function translations_export_responds_within_500ms()
    {
        $startTime = microtime(true);
        
        $response = $this->withHeaders($this->authHeaders)->getJson('/api/translations/export?locale=en');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        $this->assertLessThan(500, $responseTime, "Export response time was {$responseTime}ms, should be < 500ms");
    }

    /** @test */
    public function locales_endpoint_responds_within_200ms()
    {
        $startTime = microtime(true);
        
        $response = $this->withHeaders($this->authHeaders)->getJson('/api/translations/locales');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime, "Locales response time was {$responseTime}ms, should be < 200ms");
    }

    /** @test */
    public function tags_endpoint_responds_within_200ms()
    {
        $startTime = microtime(true);
        
        $response = $this->withHeaders($this->authHeaders)->getJson('/api/translations/tags');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime, "Tags response time was {$responseTime}ms, should be < 200ms");
    }

    /** @test */
    public function concurrent_requests_maintain_performance()
    {
        $responses = [];
        $responseTimes = [];
        
        // Simulate 5 concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $startTime = microtime(true);
            
            $response = $this->withHeaders($this->authHeaders)->getJson('/api/translations?per_page=10');
            
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;
            
            $responses[] = $response;
            $responseTimes[] = $responseTime;
        }
        
        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
        
        // Average response time should be under 200ms
        $averageResponseTime = array_sum($responseTimes) / count($responseTimes);
        $this->assertLessThan(200, $averageResponseTime, "Average response time was {$averageResponseTime}ms, should be < 200ms");
        
        // No single request should exceed 300ms
        foreach ($responseTimes as $responseTime) {
            $this->assertLessThan(300, $responseTime, "Individual response time was {$responseTime}ms, should be < 300ms");
        }
    }
}
