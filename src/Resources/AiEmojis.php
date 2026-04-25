<?php

declare(strict_types=1);

namespace Klipy\Resources;

/**
 * AI Emojis — has all standard endpoints plus async generation.
 */
class AiEmojis extends AbstractResource
{
    protected string $segment = 'ai-emojis';

    /**
     * Kick off an AI emoji generation job.
     * Returns a job/task identifier you can poll with status().
     */
    public function generate(string $prompt, ?string $customerId = null, array $extra = []): array
    {
        $body = array_filter(array_merge([
            'prompt' => $prompt,
            'customer_id' => $customerId,
        ], $extra), fn ($v) => $v !== null);

        return $this->client->request('POST', "{$this->segment}/generate", body: $body);
    }

    /**
     * Check the status of a previously-submitted generation job.
     */
    public function status(string $jobId): array
    {
        return $this->client->request('GET', "{$this->segment}/generate/{$jobId}");
    }
}
