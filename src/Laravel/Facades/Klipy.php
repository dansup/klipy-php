<?php

declare(strict_types=1);

namespace Klipy\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Klipy\Resources\AiEmojis;
use Klipy\Resources\Clips;
use Klipy\Resources\Gifs;
use Klipy\Resources\Memes;
use Klipy\Resources\SearchSuggestions;
use Klipy\Resources\Stickers;

/**
 * @method static Gifs gifs()
 * @method static Stickers stickers()
 * @method static Clips clips()
 * @method static Memes memes()
 * @method static AiEmojis aiEmojis()
 * @method static SearchSuggestions searchSuggestions()
 *
 * @see \Klipy\Klipy
 */
class Klipy extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Klipy\Klipy::class;
    }
}
