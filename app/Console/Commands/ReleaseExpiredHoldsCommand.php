<?php

namespace App\Console\Commands;

use App\Jobs\ReleaseExpiredHoldsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredHoldsCommand extends Command
{
    protected $signature = 'holds:release-expired {--limit=100 : Number of holds to process}';

    protected $description = 'Release expired holds and return stock to available';

    private const LOCK_KEY = 'release_expired_holds_lock';
    private const LOCK_TTL = 60; // seconds

    public function handle(): int
    {
        // Prevent duplicate execution using cache lock
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL);

        if (! $lock->get()) {
            $this->warn('Another instance of this command is already running.');

            return Command::FAILURE;
        }

        try {
            $this->info('Starting to release expired holds...');

            $limit = (int) $this->option('limit');
            ReleaseExpiredHoldsJob::dispatch($limit);

            $this->info("Dispatched job to release up to {$limit} expired holds.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch ReleaseExpiredHoldsJob', [
                'error' => $e->getMessage(),
            ]);

            $this->error('Failed to dispatch job: '.$e->getMessage());

            return Command::FAILURE;
        } finally {
            $lock->release();
        }
    }
}

