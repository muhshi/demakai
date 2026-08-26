<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileApiTest extends TestCase
{
    /**
     * Test mobile search endpoint.
     */
    public function test_mobile_search_endpoint_returns_success()
    {
        $response = $this->getJson('/api/v1/search?q=padi&limit=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'query',
                    'results',
                    'total',
                ],
                'meta' => [
                    'version',
                    'search_method',
                ]
            ]);
    }

    /**
     * Test mobile hierarchy endpoint.
     */
    public function test_mobile_hierarchy_endpoint_returns_categories()
    {
        $response = $this->getJson('/api/v1/kbli/hierarchy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => ['kode', 'judul', 'deskripsi', 'level', 'is_leaf']
                ]
            ]);
    }

    /**
     * Test sync check endpoint.
     */
    public function test_sync_check_returns_bundle_metadata()
    {
        $response = $this->getJson('/api/v1/sync/check');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'version',
                    'kbli_count',
                    'kbji_count',
                    'download_url',
                ]
            ]);
    }

    /**
     * Test offline bundle download endpoint.
     */
    public function test_sync_bundle_download()
    {
        $response = $this->get('/api/v1/sync/bundle');
        $response->assertStatus(200);
    }

    /**
     * Test crowdsourcing single submission.
     */
    public function test_crowdsourcing_submission_store()
    {
        $payload = [
            'type' => 'KBLI',
            'kode' => '01111',
            'content' => 'Budidaya tanaman jagung manis di lahan sawah tadah hujan',
            'submitter_name' => 'Petugas Sensus Test',
            'device_id' => 'device-test-123',
        ];

        $response = $this->postJson('/api/v1/submissions', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'kode', 'status']
            ]);
    }

    /**
     * Test bulk offline submissions sync.
     */
    public function test_crowdsourcing_bulk_sync()
    {
        $payload = [
            'submissions' => [
                [
                    'type' => 'KBLI',
                    'kode' => '01112',
                    'content' => 'Petani gandum lokal di dataran tinggi',
                    'device_id' => 'device-test-123',
                ],
                [
                    'type' => 'KBJI',
                    'kode' => '6111',
                    'content' => 'Pekerja pencabut rumput liar pada kebun tebu',
                    'device_id' => 'device-test-123',
                ],
            ]
        ];

        $response = $this->postJson('/api/v1/submissions/bulk-sync', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'synced_count' => 2,
                ]
            ]);
    }
}
