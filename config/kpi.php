<?php

return [
    'low_performance_threshold' => (float) env('KPI_LOW_PERFORMANCE_THRESHOLD', 60),
    'default_target_value' => (float) env('KPI_DEFAULT_TARGET_VALUE', 100),
    'cache_ttl' => (int) env('KPI_CACHE_TTL', 60),
    'mail_low_performance_notification' => (bool) env('KPI_MAIL_LOW_NOTIFICATION', false),
    'company_name' => env('KPI_COMPANY_NAME', config('app.name', 'KPI System')),
];
