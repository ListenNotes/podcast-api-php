<?php


namespace ListenNotes\PodcastApi\Exception;

class InvalidRequestException extends ListenApiException
{
    const STATUS = 400;
    // Invalid parameters were supplied to Listen API
}
