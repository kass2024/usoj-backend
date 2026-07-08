<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

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

        $out = [];
        foreach ($requests as $key => $req) {
            /** @var Response $response */
            $response = $responses[$key] ?? null;
            if (!$response || !$response->successful()) {
                $body = $response ? $response->body() : 'No response';
                $out[$key] = 'Gemini API error: ' . $body;
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

        $response = Http::connectTimeout((int) config('gemini.connect_timeout', 15))
            ->timeout((int) config('gemini.timeout'))
            ->withQueryParameters(['key' => config('gemini.api_key')])
            ->post($url, $this->buildPayload($prompt, $system, $jsonMode, $tokens));

        if (!$response->successful()) {
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
            'temperature' => 0.3,
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
        if (preg_match('/```json\s*(.*?)\s*```/s', $raw, $m)) {
            $raw = $m[1];
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $raw, $m)) {
            $raw = $m[1];
        }

        $decoded = json_decode(trim($raw), true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Gemini did not return valid JSON.');
        }

        return $decoded;
    }
}
