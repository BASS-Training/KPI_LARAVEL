<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('kpi-channel', function (?User $user) {
    return $user !== null;
});
