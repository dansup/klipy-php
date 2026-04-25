<?php

declare(strict_types=1);

namespace Klipy\Resources;

use Klipy\Klipy;
use Klipy\Responses\PaginatedResponse;

/**
 * Shared endpoints for content types that follow the standard Klipy structure
 * (gifs, stickers, clips, memes, ai-emojis).
 *
 * Each subclass only needs to set $segment (the URL path slug, e.g. "gifs").
 */
abstract class AbstractResource
{
    protected Klipy $client;

    /** URL segment used by this resource — e.g. "gifs", "stickers", "clips", "memes", "ai-emojis". */
    protected string $segment;

    public function __construct(Klipy $client)
    {
        $this->client = $client;
    }

    /**
     * Trending items.
     *
     * @param  int     $perPage  8–50, default 24
     * @param  int     $page     1-based page number
     * @param  ?string $rating   'g' | 'pg' | 'pg-13' | 'r'
     * @param  ?string $locale   e.g. 'en_US'
     * @param  ?string $customerId  Pass to receive ads in trending feed
     * @param  array   $extra    Any additional query params (incl. ad-min-width, ad-max-height, etc.)
     */
    public function trending(
        int $perPage = 24,
        int $page = 1,
        ?string $rating = null,
        ?string $locale = null,
        ?string $customerId = null,
        array $extra = []
    ): PaginatedResponse {
        $data = $this->client->request('GET', "{$this->segment}/trending", array_merge([
            'per_page' => $perPage,
            'page' => $page,
            'rating' => $rating,
            'locale' => $locale,
            'customer_id' => $customerId,
        ], $extra));

        return PaginatedResponse::fromResponse($data);
    }

    /**
     * Search.
     */
    public function search(
        string $query,
        int $perPage = 24,
        int $page = 1,
        ?string $rating = null,
        ?string $locale = null,
        ?string $customerId = null,
        array $extra = []
    ): PaginatedResponse {
        $data = $this->client->request('GET', "{$this->segment}/search", array_merge([
            'q' => $query,
            'per_page' => $perPage,
            'page' => $page,
            'rating' => $rating,
            'locale' => $locale,
            'customer_id' => $customerId,
        ], $extra));

        return PaginatedResponse::fromResponse($data);
    }

    /**
     * List categories for this content type.
     */
    public function categories(?string $locale = null, array $extra = []): array
    {
        return $this->client->request('GET', "{$this->segment}/categories", array_merge([
            'locale' => $locale,
        ], $extra));
    }

    /**
     * Recent items used by a given customer (per-user history).
     * Ad-related params (ad-min-width / ad-max-height etc.) can be passed in $extra.
     */
    public function recent(
        string $customerId,
        int $perPage = 24,
        int $page = 1,
        ?string $locale = null,
        array $extra = []
    ): PaginatedResponse {
        $data = $this->client->request('GET', "{$this->segment}/recent/{$customerId}", array_merge([
            'per_page' => $perPage,
            'page' => $page,
            'locale' => $locale,
        ], $extra));

        return PaginatedResponse::fromResponse($data);
    }

    /**
     * Fetch a single item by its slug / ID.
     */
    public function item(string $slug): array
    {
        return $this->client->request('GET', "{$this->segment}/{$slug}");
    }

    /**
     * Hide a previously-used item from a customer's recent list.
     */
    public function hideFromRecent(string $customerId, string $slug): array
    {
        return $this->client->request('DELETE', "{$this->segment}/recent/{$customerId}/{$slug}");
    }

    /**
     * Register a share event (improves trending/recommendation signals).
     */
    public function share(string $slug, ?string $customerId = null, array $extra = []): array
    {
        $body = array_filter(array_merge([
            'customer_id' => $customerId,
        ], $extra), fn ($v) => $v !== null);

        return $this->client->request('POST', "{$this->segment}/{$slug}/share", body: $body ?: null);
    }

    /**
     * Report an item.
     */
    public function report(string $slug, ?string $reason = null, ?string $customerId = null, array $extra = []): array
    {
        $body = array_filter(array_merge([
            'reason' => $reason,
            'customer_id' => $customerId,
        ], $extra), fn ($v) => $v !== null);

        return $this->client->request('POST', "{$this->segment}/{$slug}/report", body: $body ?: null);
    }
}
