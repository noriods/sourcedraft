<?php

namespace App\Jobs;

use App\Models\DraftJob;
use App\Services\Sd;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunDraft implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public int $draftJobId) {}

    public function handle(Sd $sd): void
    {
        $j = DraftJob::findOrFail($this->draftJobId);
        $j->update(['status' => 'running']);
        try {
            $out = $sd->run(['draft', $j->project, $j->recipe, '--topic', $j->topic]);
            [$run, $status] = explode(' ', $out) + [1 => ''];
            $j->update(['status' => $status === 'failed' ? 'failed' : 'done', 'run' => basename($run),
                'error' => $status === 'failed' ? 'The citation check failed. Open the run to see why.' : null]);
        } catch (\Throwable $e) {
            $j->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }
    }
}
