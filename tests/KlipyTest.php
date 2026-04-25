<?php

declare(strict_types=1);

namespace Klipy\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Klipy\Exceptions\KlipyApiException;
use Klipy\Klipy;
use Klipy\Responses\PaginatedResponse;
use PHPUnit\Framework\TestCase;

class KlipyTest extends TestCase
{
    /** @var array<int, Request> */
    private array $sent = [];

    private function clientWith(array $responses): Klipy
    {
        $this->sent = [];
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->sent));

        return new Klipy('test-key', [
            'default_locale' => 'en_US',
            'http_client' => new Client(['handler' => $stack, 'http_errors' => false]),
        ]);
    }

    private function paginatedBody(array $items): string
    {
        return json_encode([
            'result' => true,
            'data' => [
                'data' => $items,
                'current_page' => 1,
                'per_page' => count($items),
                'has_next' => true,
            ],
        ]);
    }

    public function test_trending_hits_correct_url_with_default_locale(): void
    {
        $klipy = $this->clientWith([
            new Response(200, [], $this->paginatedBody([['slug' => 'a'], ['slug' => 'b']])),
        ]);

        $page = $klipy->gifs()->trending(perPage: 12);

        $this->assertInstanceOf(PaginatedResponse::class, $page);
        $this->assertCount(2, $page);
        $this->assertSame('a', $page[0]['slug']);
        $this->assertTrue($page->hasNext);
        $this->assertSame(2, $page->nextPage());

        $req = $this->sent[0]['request'];
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/api/v1/test-key/gifs/trending', $req->getUri()->getPath());

        parse_str($req->getUri()->getQuery(), $query);
        $this->assertSame('12', $query['per_page']);
        $this->assertSame('1', $query['page']);
        $this->assertSame('en_US', $query['locale']);
        $this->assertArrayNotHasKey('rating', $query); // null params are stripped
    }

    public function test_search_passes_query_string(): void
    {
        $klipy = $this->clientWith([
            new Response(200, [], $this->paginatedBody([])),
        ]);

        $klipy->stickers()->search('cat', perPage: 8, locale: 'ge_GE');

        $req = $this->sent[0]['request'];
        $this->assertSame('/api/v1/test-key/stickers/search', $req->getUri()->getPath());
        parse_str($req->getUri()->getQuery(), $q);
        $this->assertSame('cat', $q['q']);
        $this->assertSame('8', $q['per_page']);
        $this->assertSame('ge_GE', $q['locale']); // per-call overrides default
    }

    public function test_recent_includes_customer_id_in_path(): void
    {
        $klipy = $this->clientWith([
            new Response(200, [], $this->paginatedBody([])),
        ]);

        $klipy->gifs()->recent('user-123', extra: ['ad-min-width' => 50]);

        $req = $this->sent[0]['request'];
        $this->assertSame('/api/v1/test-key/gifs/recent/user-123', $req->getUri()->getPath());
        parse_str($req->getUri()->getQuery(), $q);
        $this->assertSame('50', $q['ad-min-width']);
    }

    public function test_hide_from_recent_uses_delete(): void
    {
        $klipy = $this->clientWith([
            new Response(200, [], '{"result":true,"data":{}}'),
        ]);

        $klipy->memes()->hideFromRecent('user-123', 'slug-abc');

        $req = $this->sent[0]['request'];
        $this->assertSame('DELETE', $req->getMethod());
        $this->assertSame('/api/v1/test-key/memes/recent/user-123/slug-abc', $req->getUri()->getPath());
    }

    public function test_share_posts_json_body(): void
    {
        $klipy = $this->clientWith([
            new Response(200, [], '{"result":true,"data":{}}'),
        ]);

        $klipy->clips()->share('slug-1', customerId: 'user-123');

        $req = $this->sent[0]['request'];
        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('/api/v1/test-key/clips/slug-1/share', $req->getUri()->getPath());
        $this->assertSame(['customer_id' => 'user-123'], json_decode((string) $req->getBody(), true));
    }

    public function test_ai_emoji_generate_and_status(): void
    {
        $klipy = $this->clientWith([
            new Response(200, [], '{"result":true,"data":{"id":"job_1","status":"pending"}}'),
            new Response(200, [], '{"result":true,"data":{"id":"job_1","status":"completed"}}'),
        ]);

        $job = $klipy->aiEmojis()->generate('happy capybara');
        $this->assertSame('job_1', $job['data']['id']);
        $this->assertSame('POST', $this->sent[0]['request']->getMethod());
        $this->assertSame('/api/v1/test-key/ai-emojis/generate', $this->sent[0]['request']->getUri()->getPath());

        $status = $klipy->aiEmojis()->status('job_1');
        $this->assertSame('completed', $status['data']['status']);
        $this->assertSame('/api/v1/test-key/ai-emojis/generate/job_1', $this->sent[1]['request']->getUri()->getPath());
    }

    public function test_api_error_throws_with_status_and_body(): void
    {
        $klipy = $this->clientWith([
            new Response(401, [], '{"result":false,"message":"invalid api key"}'),
        ]);

        try {
            $klipy->gifs()->trending();
            $this->fail('Expected KlipyApiException');
        } catch (KlipyApiException $e) {
            $this->assertSame(401, $e->getStatusCode());
            $this->assertStringContainsString('invalid api key', $e->getMessage());
            $this->assertSame('invalid api key', $e->response['message']);
        }
    }
}
