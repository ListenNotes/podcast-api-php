<?php

declare(strict_types=1);

namespace ListenNotes\PodcastApi;

final class Client extends Http\Curl
{
    public const VERSION = '3.1.0';

    use ApiMethods;
}
