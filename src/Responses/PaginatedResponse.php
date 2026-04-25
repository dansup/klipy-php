<?php

declare(strict_types=1);

namespace Klipy\Responses;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Wraps Klipy's paginated response shape:
 *
 *   {
 *     "result": true,
 *     "data": {
 *       "data": [...items...],
 *       "current_page": 1,
 *       "per_page": 24,
 *       "has_next": true
 *     }
 *   }
 *
 * Implements ArrayAccess + Countable + IteratorAggregate so it acts like the
 * items array for most everyday use, while still exposing pagination state.
 */
class PaginatedResponse implements ArrayAccess, Countable, IteratorAggregate
{
    /** @var array<int, array> */
    public readonly array $items;
    public readonly int $currentPage;
    public readonly int $perPage;
    public readonly bool $hasNext;
    public readonly array $raw;

    public function __construct(array $items, int $currentPage, int $perPage, bool $hasNext, array $raw)
    {
        $this->items = $items;
        $this->currentPage = $currentPage;
        $this->perPage = $perPage;
        $this->hasNext = $hasNext;
        $this->raw = $raw;
    }

    public static function fromResponse(array $response): self
    {
        $data = $response['data'] ?? [];

        return new self(
            items: $data['data'] ?? [],
            currentPage: (int) ($data['current_page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 0),
            hasNext: (bool) ($data['has_next'] ?? false),
            raw: $response,
        );
    }

    public function nextPage(): ?int
    {
        return $this->hasNext ? $this->currentPage + 1 : null;
    }

    public function toArray(): array
    {
        return $this->items;
    }

    // ----- Countable -----
    public function count(): int
    {
        return count($this->items);
    }

    // ----- IteratorAggregate -----
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->items);
    }

    // ----- ArrayAccess -----
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('PaginatedResponse is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('PaginatedResponse is immutable.');
    }
}
