<?php

namespace App\Console\Commands;

use App\Services\SearchService;
use Illuminate\Console\Command;

class TestSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:test {query : The search query} {--limit=5}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Hybrid Search Service';

    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        parent::__construct();
        $this->searchService = $searchService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = $this->argument('query');
        $limit = (int) $this->option('limit');

        $this->info("Searching for: '$query' (Limit: $limit)");
        $this->info("------------------------------------------------");

        $startTime = microtime(true);

        try {
            $results = $this->searchService->search($query, $limit);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if (empty($results)) {
                $this->warn("No results found.");
                return;
            }

            $this->info("Found " . count($results) . " results in {$duration}ms:");
            $this->newLine();

            foreach ($results as $index => $item) {
                $rank = $index + 1;
                $score = isset($item->distance) ? number_format((1 - $item->distance) * 100, 2) . '%' : 'N/A'; // Convert distance to similarity %
                $source = class_basename($item);

                $this->line("<comment>#{$rank} [{$source}] {$item->kode} - {$item->judul}</comment>");
                $this->line("   Similarity: {$score}");
                $this->line("   Deskripsi: " . \Illuminate\Support\Str::limit($item->deskripsi, 100));
                $this->newLine();
            }

        } catch (\Exception $e) {
            $this->error("Search Error: " . $e->getMessage());
        }
    }
}
