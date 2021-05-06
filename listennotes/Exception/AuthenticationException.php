<?php


namespace ListenNotes\PodcastApi\Exception;

class AuthenticationException extends ListenApiException
{
    const STATUS = 401;
    const MESSAGE = 'Authentication with Listen API failed';

    public function __toString()
    {
        return self::MESSAGE;
    }
}
