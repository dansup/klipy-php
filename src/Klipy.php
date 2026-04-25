<?php

declare(strict_types=1);

namespace Klipy;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Klipy\Exceptions\KlipyApiException;
use Klipy\Exceptions\KlipyException;
use Klipy\Resources\AiEmojis;
use Klipy\Resources\Clips;
use Klipy\Resources\Gifs;
use Klipy\Resources\Memes;
use Klipy\Resources\SearchSuggestions;
use Klipy\Resources\Stickers;

/**
 * Klipy API client.
 *
 * Usage:
 *   $klipy = new Klipy('your-api-key');
 *   $trending = $klipy->gifs()->trending(perPage: 24, locale: 'en_US');
 *
 * Configurable options (passed via constructor $options array):
 *   - base_url        (string)  default: https://api.klipy.com/api/v1
 *   - timeout         (float)   default: 10.0 seconds
 *   - default_locale  (?string) e.g. 'en_US' — applied when not passed per-call
 *   - http_client     (ClientInterface) inject your own Guzzle-compatible client
 */
class Klipy
{
    public const DEFAULT_BASE_URL = 'https://api.klipy.com/api/v1';

    protected ClientInterface $http;
    protected string $apiKey;
    protected string $baseUrl;
    protected ?string $defaultLocale;

    protected ?Gifs $gifs = null;
    protected ?Stickers $stickers = null;
    protected ?Clips $clips = null;
    protected ?Memes $memes = null;
    protected ?AiEmojis $aiEmojis = null;
    protected ?SearchSuggestions $searchSuggestions = null;

    public function __construct(string $apiKey, array $options = [])
    {
        if ($apiKey === '') {
            throw new KlipyException('API key is required.');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($options['base_url'] ?? self::DEFAULT_BASE_URL, '/');
        $this->defaultLocale = $options['default_locale'] ?? null;

        $this->http = $options['http_client'] ?? new GuzzleClient([
            'timeout' => $options['timeout'] ?? 10.0,
            'http_errors' => false,
        ]);
    }

    public function gifs(): Gifs
    {
        return $this->gifs ??= new Gifs($this);
    }

    public function stickers(): Stickers
    {
        return $this->stickers ??= new Stickers($this);
    }

    public function clips(): Clips
    {
        return $this->clips ??= new Clips($this);
    }

    public function memes(): Memes
    {
        return $this->memes ??= new Memes($this);
    }

    public function aiEmojis(): AiEmojis
    {
        return $this->aiEmojis ??= new AiEmojis($this);
    }

    public function searchSuggestions(): SearchSuggestions
    {
        return $this->searchSuggestions ??= new SearchSuggestions($this);
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getDefaultLocale(): ?string
    {
        return $this->defaultLocale;
    }

    /**
     * Perform an HTTP request against the Klipy API.
     * Path should NOT include the base URL or API key — those are prepended.
     *
     * @param  string  $method   HTTP method (GET, POST, DELETE, ...)
     * @param  string  $path     Path after the API key, e.g. "gifs/trending"
     * @param  array   $query    Query string params
     * @param  array|null  $body Optional JSON body for POST/PUT
     * @return array  Decoded response body
     *
     * @throws KlipyApiException on non-2xx responses
     * @throws KlipyException on transport errors
     */
    public function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        // Apply default locale if caller didn't specify one (null counts as "not specified")
        if ($this->defaultLocale && ($query['locale'] ?? null) === null) {
            $query['locale'] = $this->defaultLocale;
        }

        // Strip nulls so we don't send "param=" for unset values
        $query = array_filter($query, fn ($v) => $v !== null);

        $url = sprintf('%s/%s/%s', $this->baseUrl, $this->apiKey, ltrim($path, '/'));

        $options = [
            'query' => $query,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'klipy-php/1.0',
            ],
        ];

        if ($body !== null) {
            $options['json'] = $body;
        }

        try {
            $response = $this->http->request($method, $url, $options);
        } catch (GuzzleException $e) {
            throw new KlipyException(
                'HTTP transport error: '.$e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        $status = $response->getStatusCode();
        $rawBody = (string) $response->getBody();
        $decoded = json_decode($rawBody, true);

        if ($status >= 400) {
            throw new KlipyApiException(
                sprintf('Klipy API error (%d): %s', $status, $this->extractErrorMessage($decoded, $rawBody)),
                $status,
                $decoded ?: []
            );
        }

        if (! is_array($decoded)) {
            throw new KlipyException('Unexpected non-JSON response from Klipy API.');
        }

        return $decoded;
    }

    protected function extractErrorMessage(mixed $decoded, string $raw): string
    {
        if (is_array($decoded)) {
            return $decoded['message']
                ?? $decoded['error']
                ?? $decoded['data']['message']
                ?? substr($raw, 0, 200);
        }

        return substr($raw, 0, 200);
    }
}
