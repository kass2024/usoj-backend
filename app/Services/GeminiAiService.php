<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GeminiAiService
{
    public function isConfigured(): bool
    {
        return !empty(config('gemini.api_key'));
    }

    public function usesFallbackOnly(): bool
    {
        return (bool) config('gemini.fallback_only', false);
    }

    public function parallelLimit(): int
    {
        if (config('gemini.sequential_mode', false)) {
            return 1;
        }

        return max(1, min(16, (int) config('gemini.parallel_requests', 2)));
    }

    public function generateText(string $prompt, ?string $system = null, ?int $maxTokens = null): string
    {
        $response = $this->sendRequestWithRetry($prompt, $system, false, $maxTokens);

        return $this->extractText($response);
    }

    public function generateJson(string $prompt, ?string $system = null, ?int $maxTokens = null): array
    {
        $response = $this->sendRequestWithRetry($prompt, $system, true, $maxTokens);
        $raw = $this->extractText($response);

        return $this->decodeJson($raw);
    }

    /**
     * @return array<string, array|string>
     */
    public function poolGenerateJson(array $requests, ?int $maxTokens = null): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        if ($requests === []) {
            return [];
        }

        if (config('gemini.sequential_mode', false)) {
            return $this->sequentialGenerateJson($requests, $maxTokens);
        }

        return $this->parallelGenerateJson($requests, $maxTokens);
    }

    /**
     * @param  array<string, array{prompt: string, system?: string|null}>  $requests
     * @return array<string, array|string>
     */
    private function sequentialGenerateJson(array $requests, ?int $maxTokens): array
    {
        $out = [];
        $delayMs = max(0, (int) config('gemini.request_delay_ms', 500));

        foreach ($requests as $key => $req) {
            $out[$key] = $this->tryGenerateJson(
                $req['prompt'],
                $req['system'] ?? null,
                $maxTokens
            );

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        return $out;
    }

    /**
     * @param  array<string, array{prompt: string, system?: string|null}>  $requests
     * @return array<string, array|string>
     */
    private function parallelGenerateJson(array $requests, ?int $maxTokens): array
    {
        $model = config('gemini.model');
        $url = rtrim(config('gemini.base_url'), '/') . "/models/{$model}:generateContent";
        $timeout = (int) config('gemini.timeout');
        $connectTimeout = (int) config('gemini.connect_timeout', 30);
        $tokens = $maxTokens ?? (int) config('gemini.question_max_tokens', 2048);

        try {
            $responses = Http::pool(function (Pool $pool) use ($requests, $url, $timeout, $connectTimeout, $tokens) {
                foreach ($requests as $key => $req) {
                    $pool->as($key)
                        ->connectTimeout($connectTimeout)
                        ->timeout($timeout)
                        ->withQueryParameters(['key' => config('gemini.api_key')])
                        ->post($url, $this->buildPayload(
                            $req['prompt'],
                            $req['system'] ?? null,
                            true,
                            $tokens
                        ));
                }
            });
        } catch (Throwable $e) {
            $message = $this->sanitizeError('Gemini pool error: ' . $e->getMessage());
            $out = [];
            foreach (array_keys($requests) as $key) {
                $out[$key] = $message;
            }

            return $out;
        }

        $out = [];
        $retryQueue = [];

        foreach ($requests as $key => $req) {
            $parsed = $this->parsePoolResponse($responses[$key] ?? null);
            if (is_array($parsed)) {
                $out[$key] = $parsed;
            } else {
                $out[$key] = $parsed;
                if ($this->isRetryableError((string) $parsed)) {
                    $retryQueue[$key] = $req;
                }
            }
        }

        if ($retryQueue !== []) {
            $delayMs = max(0, (int) config('gemini.request_delay_ms', 500));
            foreach ($retryQueue as $key => $req) {
                $out[$key] = $this->tryGenerateJson(
                    $req['prompt'],
                    $req['system'] ?? null,
                    $maxTokens
                );

                if ($delayMs > 0) {
                    usleep($delayMs * 1000);
                }
            }
        }

        return $out;
    }

    /**
     * @return array|string
     */
    private function tryGenerateJson(string $prompt, ?string $system = null, ?int $maxTokens = null): array|string
    {
        $attempts = max(1, (int) config('gemini.retry_attempts', 3));
        $lastError = 'Unknown Gemini error';

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $this->generateJson($prompt, $system, $maxTokens);
            } catch (RuntimeException $e) {
                $lastError = $this->sanitizeError($e->getMessage());

                if ($attempt < $attempts && $this->isRetryableError($lastError)) {
                    usleep(max(1, (int) config('gemini.retry_delay_ms', 1500)) * 1000 * $attempt);
                    continue;
                }

                break;
            }
        }

        return $lastError;
    }

    /**
     * @return array|string
     */
    private function parsePoolResponse(mixed $response): array|string
    {
        if ($response instanceof Throwable) {
            return $this->sanitizeError('Gemini connection error: ' . $response->getMessage());
        }

        if (!$response instanceof Response) {
            return 'Gemini API error: No response received';
        }

        if ($response->failed()) {
            return $this->sanitizeError('Gemini API error: ' . $response->body());
        }

        try {
            $raw = $this->extractText($response);

            return $this->decodeJson($raw);
        } catch (RuntimeException $e) {
            return $this->sanitizeError($e->getMessage());
        }
    }

    private function sendRequestWithRetry(
        string $prompt,
        ?string $system,
        bool $jsonMode,
        ?int $maxTokens
    ): Response {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Gemini API key is not configured. Set GOOGLE_AI_API_KEY in .env');
        }

        $attempts = max(1, (int) config('gemini.retry_attempts', 3));
        $lastError = 'Unknown Gemini error';

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $this->sendRequest($prompt, $system, $jsonMode, $maxTokens);
            } catch (RuntimeException $e) {
                $lastError = $this->sanitizeError($e->getMessage());

                if ($attempt < $attempts && $this->isRetryableError($lastError)) {
                    usleep(max(1, (int) config('gemini.retry_delay_ms', 1500)) * 1000 * $attempt);
                    continue;
                }

                throw new RuntimeException($lastError, 0, $e);
            }
        }

        throw new RuntimeException($lastError);
    }

    private function sendRequest(string $prompt, ?string $system, bool $jsonMode, ?int $maxTokens): Response
    {
        $model = config('gemini.model');
        $url = rtrim(config('gemini.base_url'), '/') . "/models/{$model}:generateContent";
        $tokens = $maxTokens ?? ($jsonMode
            ? (int) config('gemini.question_max_tokens', 2048)
            : (int) config('gemini.max_output_tokens', 8192));

        try {
            $response = Http::connectTimeout((int) config('gemini.connect_timeout', 30))
                ->timeout((int) config('gemini.timeout'))
                ->withQueryParameters(['key' => config('gemini.api_key')])
                ->post($url, $this->buildPayload($prompt, $system, $jsonMode, $tokens));
        } catch (ConnectionException $e) {
            throw new RuntimeException('Gemini connection error: ' . $e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            throw new RuntimeException('Gemini API error: ' . $response->body());
        }

        return $response;
    }

    private function buildPayload(string $prompt, ?string $system, bool $jsonMode, int $maxTokens): array
    {
        $parts = [];
        if ($system) {
            $parts[] = ['text' => $system];
        }
        $parts[] = ['text' => $prompt];

        $generationConfig = [
            'temperature' => 0.2,
            'maxOutputTokens' => $maxTokens,
        ];

        if ($jsonMode) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        return [
            'contents' => [
                ['role' => 'user', 'parts' => $parts],
            ],
            'generationConfig' => $generationConfig,
        ];
    }

    private function extractText(Response $response): string
    {
        $parts = data_get($response->json(), 'candidates.0.content.parts', []);
        $text = '';

        foreach ($parts as $part) {
            if (!empty($part['text'])) {
                $text .= $part['text'];
            }
        }

        $text = trim($text);

        if ($text === '') {
            $finishReason = data_get($response->json(), 'candidates.0.finishReason');
            throw new RuntimeException(
                'Gemini returned an empty response' . ($finishReason ? " ({$finishReason})" : '') . '.'
            );
        }

        return $text;
    }

    private function decodeJson(string $raw): array
    {
        $raw = trim($raw);

        if (preg_match('/```json\s*(.*?)\s*```/s', $raw, $m)) {
            $raw = trim($m[1]);
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $raw, $m)) {
            $raw = trim($m[1]);
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $repaired = preg_replace('/,\s*([}\]])/', '$1', $raw);
        if (is_string($repaired)) {
            $decoded = json_decode($repaired, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (preg_match('/\[.*\]/s', $raw, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('Gemini did not return valid JSON.');
    }

    private function isRetryableError(string $message): bool
    {
        $needles = [
            'cURL error 28',
            'Resolving timed out',
            'Connection timed out',
            'Could not resolve host',
            'Connection refused',
            'SSL connection',
            'Empty reply from server',
            'Recv failure',
            'Gemini connection error',
        ];

        foreach ($needles as $needle) {
            if (stripos($message, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeError(string $message): string
    {
        $message = preg_replace('/key=[^&\s]+/i', 'key=[REDACTED]', $message) ?? $message;
        $message = preg_replace('/\?key=[^\s]+/i', '?key=[REDACTED]', $message) ?? $message;

        if (strlen($message) > 280) {
            $message = substr($message, 0, 277) . '...';
        }

        return trim($message);
    }
}
