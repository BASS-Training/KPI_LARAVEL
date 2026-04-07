<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'payload' => $this->payload,
            'is_read' => $this->is_read,
            'read_at' => optional($this->read_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
