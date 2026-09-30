<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Content;
use App\Models\ContentSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentIngestionApiTest extends TestCase
{
    use RefreshDatabase;

    protected Brand $brand;

    protected ContentSource $source;

    protected string $rawApiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brand = Brand::factory()->create([
            'name' => 'NewsCrestle',
            'slug' => 'newscrestle',
            'status' => Brand::STATUS_ACTIVE,
        ]);

        $this->source = ContentSource::create([
            'brand_id' => $this->brand->id,
            'name' => 'NewsCrestle CMS API',
            'type' => ContentSource::TYPE_API,
            'status' => ContentSource::STATUS_ACTIVE,
        ]);

        $this->rawApiKey = $this->source->rotateApiKey();
    }

    public function test_can_ingest_content_with_valid_bearer_token(): void
    {
        $payload = [
            'external_id' => 'article-99412',
            'type' => 'article',
            'title' => 'Deep Space Observatory Detects Novel Gravitational Anomaly',
            'summary' => 'Astrophysicists record synchronized gravitational pulses from distant sector.',
            'body' => 'Comprehensive breakdown of observational astrophysics datasets.',
            'url' => 'https://newscrestle.example.com/articles/deep-space-anomaly-99412',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->rawApiKey}")
            ->postJson('/api/v1/content', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'created',
                'duplicate' => false,
            ])
            ->assertJsonPath('data.external_id', 'article-99412')
            ->assertJsonPath('data.title', 'Deep Space Observatory Detects Novel Gravitational Anomaly');

        $this->assertDatabaseHas('contents', [
            'brand_id' => $this->brand->id,
            'external_id' => 'article-99412',
            'title' => 'Deep Space Observatory Detects Novel Gravitational Anomaly',
        ]);

        $this->assertNotNull($this->source->fresh()->api_key_last_used_at);
    }

    public function test_can_ingest_content_with_x_api_key_header(): void
    {
        $payload = [
            'external_id' => 'article-99413',
            'type' => 'article',
            'title' => 'Global Renewable Transition Exceeds Projections in Q3',
            'summary' => 'Clean energy infrastructure reaches milestone deployment speeds worldwide.',
        ];

        $response = $this->withHeader('X-API-Key', $this->rawApiKey)
            ->postJson('/api/v1/content', $payload);

        $response->assertStatus(201)
            ->assertJson(['status' => 'created', 'duplicate' => false]);

        $this->assertDatabaseHas('contents', [
            'external_id' => 'article-99413',
        ]);
    }

    public function test_ingest_without_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/v1/content', [
            'external_id' => 'unauth-1',
            'type' => 'article',
            'title' => 'Unauthorized Post',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'unauthenticated']);
    }

    public function test_ingest_with_invalid_api_key_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_key_12345')
            ->postJson('/api/v1/content', [
                'external_id' => 'invalid-1',
                'type' => 'article',
                'title' => 'Invalid Key Post',
            ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'invalid_api_key']);
    }

    public function test_ingest_with_revoked_source_returns_401(): void
    {
        $this->source->revokeApiKey();

        $response = $this->withHeader('Authorization', "Bearer {$this->rawApiKey}")
            ->postJson('/api/v1/content', [
                'external_id' => 'revoked-1',
                'type' => 'article',
                'title' => 'Revoked Key Post',
            ]);

        $response->assertStatus(401);
    }

    public function test_duplicate_external_id_updates_gracefully_and_returns_status_200(): void
    {
        $payload1 = [
            'external_id' => 'article-duplicate-1',
            'type' => 'article',
            'title' => 'Initial Title Announcement',
            'summary' => 'Initial summary.',
        ];

        $firstResponse = $this->withHeader('Authorization', "Bearer {$this->rawApiKey}")
            ->postJson('/api/v1/content', $payload1);

        $firstResponse->assertStatus(201);

        // Send duplicate external_id with updated title
        $payload2 = [
            'external_id' => 'article-duplicate-1',
            'type' => 'article',
            'title' => 'Revised and Updated Title Announcement',
            'summary' => 'Updated summary text.',
        ];

        $secondResponse = $this->withHeader('Authorization', "Bearer {$this->rawApiKey}")
            ->postJson('/api/v1/content', $payload2);

        $secondResponse->assertStatus(200)
            ->assertJson([
                'status' => 'updated',
                'duplicate' => true,
            ]);

        // Verify there is only ONE row with this external_id and its title is updated
        $this->assertEquals(1, Content::where('external_id', 'article-duplicate-1')->count());
        $this->assertEquals(
            'Revised and Updated Title Announcement',
            Content::where('external_id', 'article-duplicate-1')->first()->title
        );
    }
}
