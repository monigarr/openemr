<?php

/**
 * Minimal OpenAI Chat Completions client (server-side only).
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

final class OpenAiClient
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';

    public function __construct(private readonly ?string $apiKey)
    {
    }

    public function hasApiKey(): bool
    {
        return $this->apiKey !== null && $this->apiKey !== '';
    }

    /**
     * @param list<array{role:string,content:string}> $messages
     * @return array{content:string,usage:array<string,int>,model:string,raw_status:int}
     * @throws \RuntimeException
     */
    public function chatJson(string $model, array $messages): array
    {
        if (!$this->hasApiKey()) {
            throw new \RuntimeException('OpenAI API key is not configured');
        }

        $client = new Client(['timeout' => 60]);
        try {
            $response = $client->post(self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.2,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('OpenAI request failed: ' . $e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('OpenAI invalid JSON response');
        }
        $content = '';
        if (isset($decoded['choices'][0]['message']['content']) && is_string($decoded['choices'][0]['message']['content'])) {
            $content = $decoded['choices'][0]['message']['content'];
        }
        $usage = [];
        if (isset($decoded['usage']) && is_array($decoded['usage'])) {
            foreach (['prompt_tokens', 'completion_tokens', 'total_tokens'] as $k) {
                if (isset($decoded['usage'][$k])) {
                    $usage[$k] = (int) $decoded['usage'][$k];
                }
            }
        }
        $modelOut = is_string($decoded['model'] ?? null) ? $decoded['model'] : $model;

        return [
            'content' => $content,
            'usage' => $usage,
            'model' => $modelOut,
            'raw_status' => $status,
        ];
    }

    /**
     * Rough USD estimate for observability (not billing); uses public list pricing as defaults.
     *
     * @param array<string,int> $usage
     */
    public static function estimateCostUsd(string $model, array $usage): float
    {
        $in = $usage['prompt_tokens'] ?? 0;
        $out = $usage['completion_tokens'] ?? 0;
        // Defaults for gpt-4o-mini class (adjust in docs if model changes)
        $inPer1M = 0.15;
        $outPer1M = 0.60;
        if (str_contains($model, 'gpt-4o') && !str_contains($model, 'mini')) {
            $inPer1M = 2.50;
            $outPer1M = 10.00;
        }
        return round(($in / 1_000_000) * $inPer1M + ($out / 1_000_000) * $outPer1M, 6);
    }
}
