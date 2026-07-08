<?php

return [
    'api_key' => env('GOOGLE_AI_API_KEY', env('GEMINI_API_KEY')),
    'model' => env('GOOGLE_AI_MODEL', env('GEMINI_MODEL', 'gemini-2.5-flash')),
    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    'timeout' => (int) env('GEMINI_TIMEOUT', 60),
    'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 30),
    'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 8192),
    'question_max_tokens' => (int) env('GEMINI_QUESTION_MAX_TOKENS', 2048),
    'material_max_tokens' => (int) env('GEMINI_MATERIAL_MAX_TOKENS', 3072),
    'parallel_requests' => (int) env('GEMINI_PARALLEL_REQUESTS', 2),
    'sequential_mode' => filter_var(env('GEMINI_SEQUENTIAL_MODE', false), FILTER_VALIDATE_BOOL),
    'fallback_only' => filter_var(env('GEMINI_FALLBACK_ONLY', false), FILTER_VALIDATE_BOOL),
    'retry_attempts' => (int) env('GEMINI_RETRY_ATTEMPTS', 3),
    'retry_delay_ms' => (int) env('GEMINI_RETRY_DELAY_MS', 1500),
    'request_delay_ms' => (int) env('GEMINI_REQUEST_DELAY_MS', 500),
    'run_max_execution_time' => (int) env('AI_STUDIO_MAX_EXECUTION_TIME', 0),
];
