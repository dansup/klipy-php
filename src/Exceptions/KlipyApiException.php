<?php

declare(strict_types=1);

namespace Klipy\Exceptions;

class KlipyApiException extends KlipyException
{
    /** @var array<string, mixed> */
    public readonly array $response;

    public function __construct(string $message, int $status, array $response = [])
    {
        parent::__construct($message, $status);
        $this->response = $response;
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }
}
