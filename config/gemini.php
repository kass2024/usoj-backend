<?php

return [
    'api_key' => env('GOOGLE_AI_API_KEY', env('GEMINI_API_KEY')),
    'model' => env('GOOGLE_AI_MODEL', env('GEMINI_MODEL', 'gemini-2.5-flash')),
    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    'timeout' => (int) env('GEMINI_TIMEOUT', 45),
    'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 15),
    'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 8192),
    'question_max_tokens' => (int) env('GEMINI_QUESTION_MAX_TOKENS', 2048),
    'material_max_tokens' => (int) env('GEMINI_MATERIAL_MAX_TOKENS', 3072),
    'parallel_requests' => (int) env('GEMINI_PARALLEL_REQUESTS', 6),
    'run_max_execution_time' => (int) env('AI_STUDIO_MAX_EXECUTION_TIME', 0),
];
