<?php

namespace App\Jobs;

use App\Services\Contracts\HoldServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredHoldsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $limit = 100
    ) {
    }

    public function handle(HoldServiceInterface $holdService): void
    {
        Log::info('ReleaseExpiredHoldsJob started', [
            'limit' => $this->limit,
        ]);

        $releasedCount = $holdService->releaseExpiredHolds($this->limit);

        Log::info('ReleaseExpiredHoldsJob completed', [
            'released_count' => $releasedCount,
        ]);
    }
}

