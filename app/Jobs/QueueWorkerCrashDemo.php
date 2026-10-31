<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class QueueWorkerCrashDemo implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 5;
    public int $timeout = 35;

    public function handle(): void
    {
        Log::info('CHECKPOINT_DEMO_START', [
            'started_at' => now()->toDateTimeString(),
            'pid' => getmypid(),
        ]);

        sleep(30);

        Log::info('CHECKPOINT_DEMO_DONE', [
            'finished_at' => now()->toDateTimeString(),
            'pid' => getmypid(),
        ]);
    }
}