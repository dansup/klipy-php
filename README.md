# klipy-php

A PHP / Laravel client for the [Klipy API](https://docs.klipy.com) — GIFs, Stickers, Clips, Memes, and AI Emojis.

Works as a plain PHP library or a Laravel package (auto-discovered service provider + facade).

## Install

```bash
composer require dansup/klipy-php
```

Requires PHP 8.1+ and Guzzle 7+. Both are pulled in automatically.

## Get an API key

Sign up at [partner.klipy.com](https://partner.klipy.com). Test keys are free and rate-limited to 100 calls/min — request a production key in the dashboard once you're ready.

## Usage (vanilla PHP)

```php
use Klipy\Klipy;

$klipy = new Klipy('your-api-key', [
    'default_locale' => 'en_US', // optional
    'timeout' => 10.0,           // optional
]);

// Trending gifs
$trending = $klipy->gifs()->trending(perPage: 24, page: 1);

foreach ($trending as $gif) {
    echo $gif['title'], ' — ', $gif['files']['gif']['url'], PHP_EOL;
}

if ($trending->hasNext) {
    $next = $klipy->gifs()->trending(page: $trending->nextPage());
}

// Search
$results = $klipy->stickers()->search('cat', perPage: 10);

// Categories
$cats = $klipy->memes()->categories();

// Per-user recents (returns recent items + ad slots)
$recent = $klipy->gifs()->recent(
    customerId: 'user-uuid-from-your-system',
    extra: [
        'ad-min-width' => 50,
        'ad-max-width' => 401,
        'ad-min-height' => 50,
        'ad-max-height' => 250,
    ],
);

// Single item by slug
$gif = $klipy->gifs()->item('slug-from-trending-or-search');

// Hide from recents
$klipy->gifs()->hideFromRecent('user-uuid', 'slug');

// Share / report triggers
$klipy->gifs()->share('slug', customerId: 'user-uuid');
$klipy->gifs()->report('slug', reason: 'inappropriate', customerId: 'user-uuid');

// AI emoji generation (async)
$job = $klipy->aiEmojis()->generate('a happy capybara wearing sunglasses');
$status = $klipy->aiEmojis()->status($job['data']['id']);

// Search suggestions / autocomplete
$suggestions = $klipy->searchSuggestions()->suggestions();
$completions = $klipy->searchSuggestions()->autocomplete('cat');
```

## Usage (Laravel)

The service provider is auto-discovered. Publish the config:

```bash
php artisan vendor:publish --tag=klipy-config
```

Then set your key in `.env`:

```
KLIPY_API_KEY=your-api-key
KLIPY_DEFAULT_LOCALE=en_US
```

Use the facade or resolve from the container:

```php
use Klipy\Laravel\Facades\Klipy;

$trending = Klipy::gifs()->trending(perPage: 12);

// or: app(\Klipy\Klipy::class)->gifs()->trending();
```

## API surface

All five content types (`gifs`, `stickers`, `clips`, `memes`, `aiEmojis`) expose the same eight methods:

| Method | HTTP | Description |
|---|---|---|
| `trending(perPage, page, rating, locale, customerId, extra)` | `GET /{type}/trending` | Trending feed |
| `search(q, perPage, page, rating, locale, customerId, extra)` | `GET /{type}/search` | Keyword search |
| `categories(locale, extra)` | `GET /{type}/categories` | Category list |
| `recent(customerId, perPage, page, locale, extra)` | `GET /{type}/recent/{customer}` | Per-user recents (ad-eligible) |
| `item(slug)` | `GET /{type}/{slug}` | Single item lookup |
| `hideFromRecent(customerId, slug)` | `DELETE /{type}/recent/{customer}/{slug}` | Hide from recents |
| `share(slug, customerId, extra)` | `POST /{type}/{slug}/share` | Register share |
| `report(slug, reason, customerId, extra)` | `POST /{type}/{slug}/report` | Report content |

`aiEmojis()` adds:

| Method | HTTP | Description |
|---|---|---|
| `generate(prompt, customerId, extra)` | `POST /ai-emojis/generate` | Kick off generation job |
| `status(jobId)` | `GET /ai-emojis/generate/{id}` | Poll job status |

`searchSuggestions()`:

| Method | HTTP |
|---|---|
| `suggestions(locale, extra)` | `GET /search-suggestions` |
| `autocomplete(q, locale, extra)` | `GET /autocomplete` |

## Pagination

Methods that return a list (`trending`, `search`, `recent`) return a `PaginatedResponse` that implements `IteratorAggregate`, `Countable`, and `ArrayAccess`. Use it like an array, or read pagination state directly:

```php
$page = $klipy->gifs()->trending();

count($page);              // item count on this page
$page[0];                  // first item
foreach ($page as $item) {} // iterate

$page->currentPage;        // int
$page->perPage;            // int
$page->hasNext;            // bool
$page->nextPage();         // int|null — null if no next page
$page->raw;                // full decoded response, including 'result' wrapper
```

## Common parameters

- **`per_page`** — 8 to 50, default 24
- **`page`** — 1-based
- **`rating`** — `g` | `pg` | `pg-13` | `r`
- **`locale`** — `xx_YY`, e.g. `en_US`, `ge_GE`, `uk_UK`. Set a global default via the `default_locale` constructor option or the `KLIPY_DEFAULT_LOCALE` env var.

Anything not covered explicitly can be passed via the `extra` array on any method — it's merged straight into the query string (or POST body for share/report).

## Error handling

```php
use Klipy\Exceptions\KlipyApiException;
use Klipy\Exceptions\KlipyException;

try {
    $results = $klipy->gifs()->search('cats');
} catch (KlipyApiException $e) {
    // 4xx / 5xx from Klipy
    $e->getStatusCode();   // int
    $e->response;          // full decoded response array
} catch (KlipyException $e) {
    // transport / parse errors
}
```

## Custom HTTP client

Pass any `GuzzleHttp\ClientInterface` (or compatible) for testing or to attach middleware:

```php
$klipy = new Klipy('your-api-key', [
    'http_client' => new \GuzzleHttp\Client([
        'handler' => $myMockHandler,
    ]),
]);
```

## Attribution

Per Klipy's [attribution guidelines](https://docs.klipy.com/attribution): use "Search KLIPY" as the search field placeholder and display the "Powered by KLIPY" logo / watermark where appropriate.

## License

MIT.
