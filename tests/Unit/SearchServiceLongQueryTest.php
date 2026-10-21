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

    public function test_long_survey_queries_return_relevant_results(): void
    {
        $service = app(SearchService::class);
        $queries = [
            'Menjual takoyaki keliling di desa desa sebelah',
            'PEMBUDIYAAN JANGKRIK DIMULAI DARI TELUR HINGGA BESAR (SIAP PANEN)',
            'melakukan proses pengolahan bahan baku daging tepung bumbu menjadi pentol siap saji di rumah kemudian memasarkannya secara keliling menggunakan gerobak yang dimodifikasi pada sepeda motor',
            'Usaha jasa pengolahan pascapanen sarang burung walet berupa pembersihan sarang walet mentah dirty bird nest melalui proses perendaman pembersihan kotoran'
        ];

        foreach ($queries as $q) {
            $res = $service->search($q, 5, 'KBLI', false);
            $this->assertNotEmpty($res, "Query '$q' must not return empty results");
        }
    }
}
