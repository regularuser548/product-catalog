<?php

namespace App\Console\Commands;

use App\Services\RedisFilterService;
use App\Services\XmlImportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportXmlData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:xml {path : The full path to the XML file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from XML file into the database.';

    /**
     * Execute the console command.
     */
    public function handle(XmlImportService $xmlImportService, RedisFilterService $redisFilterService): void
    {
        $path = $this->argument('path');

        if (!file_exists($path)) {
            $this->error("File not found at: $path");
            return;
        }

        $this->info("Importing data from: $path");

        $startTimestamp = now();

        $xmlImportService->import($path);

        $this->info('Import complete.');

        $this->info('Rebuilding filters...');
        $redisFilterService->rebuild();

        $this->info('Total time: ' . $startTimestamp->diffForHumans(Carbon::now(), true));
    }
}
