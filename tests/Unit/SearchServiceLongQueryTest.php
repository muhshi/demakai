<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SearchService;

class SearchServiceLongQueryTest extends TestCase
{
    public function test_preprocess_identifies_core_activity_and_instrumental_modifiers(): void
    {
        $service = app(SearchService::class);
        $query = "jual pentol keliling menggunakan gerobak sepeda motor";
        $prep = $service->preprocessQuery($query);

        $this->assertContains('pentol', $prep['tokens']);
        $this->assertNotContains('menggunakan', $prep['tokens']);
        $this->assertArrayHasKey('core_activity', $prep);
        $this->assertArrayHasKey('instrumental_modifiers', $prep);
        $this->assertStringContainsString('pentol', $prep['core_activity']);
        $this->assertContains('gerobak', $prep['instrumental_modifiers']);
    }

    public function test_stopwords_filter_narrative_noise(): void
    {
        $service = app(SearchService::class);
        $query = "melakukan proses pengolahan bahan baku daging kemudian menghasilkan pentol";
        $prep = $service->preprocessQuery($query);

        $this->assertNotContains('melakukan', $prep['tokens']);
        $this->assertNotContains('proses', $prep['tokens']);
        $this->assertNotContains('kemudian', $prep['tokens']);
        $this->assertNotContains('menghasilkan', $prep['tokens']);
        $this->assertContains('pengolahan', $prep['tokens']);
        $this->assertContains('pentol', $prep['tokens']);
    }
}
