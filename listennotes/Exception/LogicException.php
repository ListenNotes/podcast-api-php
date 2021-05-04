<?php

declare(strict_types=1);

namespace ListenNotes\PodcastApiClient\Exception;

class LogicException extends \LogicException
{
    public function hello()
    {
        return 'hello';
    }
}
