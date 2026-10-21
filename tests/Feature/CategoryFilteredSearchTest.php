<?php

namespace Tests\Feature;

use App\Services\SearchService;
use Tests\TestCase;

class CategoryFilteredSearchTest extends TestCase
{
    /**
     * Test category division mapping logic.
     */
    public function test_category_division_mapping(): void
    {
        $service = app(SearchService::class);
        $this->assertEquals(['45', '46', '47'], $service->getCategoryDivisions('G'));
        $this->assertEquals(['01', '02', '03'], $service->getCategoryDivisions('A'));
        $this->assertEquals(['55', '56'], $service->getCategoryDivisions('I'));
        $this->assertEquals(['56'], $service->getCategoryDivisions('I56'));
        $this->assertEquals(['56'], $service->getCategoryDivisions('56'));
        $this->assertContains('10', $service->getCategoryDivisions('C'));
        $this->assertContains('33', $service->getCategoryDivisions('C'));
    }

    /**
     * Test search boosts matching category in SearchService.
     */
    public function test_search_boosts_matching_category_in_search_service(): void
    {
        $service = app(SearchService::class);
        // "pedagang beras" with category G (Trade) should prioritize 46/47 over 10 (Manufacture)
        $resultsG = $service->search('pedagang beras', 5, 'KBLI', false, 'G');

        $this->assertNotEmpty($resultsG, "Search results must not be empty");
        $firstKode = (string)(is_array($resultsG[0]) ? $resultsG[0]['kode'] : $resultsG[0]->kode);
        $this->assertStringStartsWith('4', $firstKode, "Category G must prioritize division 45/46/47 on top, got {$firstKode}");
    }

    /**
     * Test /api/v1/search endpoint prioritizes category.
     */
    public function test_api_v1_search_prioritizes_category(): void
    {
        $response = $this->getJson('/api/v1/search?q=pedagang+beras&category=G');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        $results = $response->json('data.results');
        $this->assertNotEmpty($results, "API search results must not be empty");

        $firstKode = (string)$results[0]['kode'];
        $this->assertStringStartsWith('4', $firstKode, "Top API result with category G must start with 4 (46/47), got {$firstKode}");
    }
}
