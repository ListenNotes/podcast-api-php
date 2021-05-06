<?php

namespace ListenNotes\PodcastApi\Exception;

class ListenApiException extends \Exception
{
    // Display a very generic error to the user
    public function getStatus()
    {
        return self::STATUS;
    }
}
