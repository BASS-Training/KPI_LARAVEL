<?php

namespace App\Events;

use App\Models\KpiIndicator;
use App\Models\KpiScore;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KPIUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly KpiScore $score,
        public readonly ?KpiIndicator $indicator = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('kpi-channel'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'kpi.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->score->user_id,
            'score' => (float) $this->score->normalized_score,
            'status' => $this->score->status,
            'indicator' => $this->indicator?->only(['id', 'name']),
            'period_start' => optional($this->score->period_start)->toDateString(),
            'period_type' => $this->score->period_type,
        ];
    }
}
