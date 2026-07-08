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

    public function parallelLimit(): int
    {
        return max(1, min(16, (int) config('gemini.parallel_requests', 8)));
    }

    public function generateText(string $prompt, ?string $system = null, ?int $maxTokens = null): string
    {
        $response = $this->sendRequest($prompt, $system, false, $maxTokens);

        return $this->extractText($response);
    }

    public function generateJson(string $prompt, ?string $system = null, ?int $maxTokens = null): array
    {
        $response = $this->sendRequest($prompt, $system, true, $maxTokens);
        $raw = $this->extractText($response);

        return $this->decodeJson($raw);
    }

    /**
     * Run multiple Gemini JSON requests in parallel (Http::pool).
     *
     * @param  array<string, array{prompt: string, system?: string|null}>  $requests
     * @return array<string, array|string> decoded JSON or error message string
     */
    public function poolGenerateJson(array $requests, ?int $maxTokens = null): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        if ($requests === []) {
            return [];
        }

        $model = config('gemini.model');
        $url = rtrim(config('gemini.base_url'), '/') . "/models/{$model}:generateContent";
        $timeout = (int) config('gemini.timeout');
        $connectTimeout = (int) config('gemini.connect_timeout', 15);
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
            $message = 'Gemini pool error: ' . $e->getMessage();
            $out = [];
            foreach (array_keys($requests) as $key) {
                $out[$key] = $message;
            }

            return $out;
        }

        $out = [];
        foreach ($requests as $key => $req) {
            $response = $responses[$key] ?? null;

            if ($response instanceof Throwable) {
                $out[$key] = 'Gemini connection error: ' . $response->getMessage();
                continue;
            }

            if (!$response instanceof Response) {
                $out[$key] = 'Gemini API error: No response received';
                continue;
            }

            if ($response->failed()) {
                $out[$key] = 'Gemini API error: ' . $response->body();
                continue;
            }

            try {
                $raw = $this->extractText($response);
                $out[$key] = $this->decodeJson($raw);
            } catch (RuntimeException $e) {
                $out[$key] = $e->getMessage();
            }
        }

        return $out;
    }

    private function sendRequest(string $prompt, ?string $system, bool $jsonMode, ?int $maxTokens): Response
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Gemini API key is not configured. Set GOOGLE_AI_API_KEY in .env');
        }

        $model = config('gemini.model');
        $url = rtrim(config('gemini.base_url'), '/') . "/models/{$model}:generateContent";
        $tokens = $maxTokens ?? ($jsonMode
            ? (int) config('gemini.question_max_tokens', 2048)
            : (int) config('gemini.max_output_tokens', 8192));

        try {
            $response = Http::connectTimeout((int) config('gemini.connect_timeout', 15))
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
        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (!$text) {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        return trim($text);
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
}
