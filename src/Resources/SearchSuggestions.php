<?php

declare(strict_types=1);

namespace Klipy\Resources;

use Klipy\Klipy;

/**
 * Search suggestions & autocomplete — these sit outside the per-content-type
 * resources and are typically applied across all media types.
 */
class SearchSuggestions
{
    protected Klipy $client;

    public function __construct(Klipy $client)
    {
        $this->client = $client;
    }

    /**
     * Suggested search queries (e.g. trending search phrases).
     */
    public function suggestions(?string $locale = null, array $extra = []): array
    {
        return $this->client->request('GET', 'search-suggestions', array_merge([
            'locale' => $locale,
        ], $extra));
    }

    /**
     * Autocomplete completions for a partial query.
     */
    public function autocomplete(string $query, ?string $locale = null, array $extra = []): array
    {
        return $this->client->request('GET', 'autocomplete', array_merge([
            'q' => $query,
            'locale' => $locale,
        ], $extra));
    }
}
