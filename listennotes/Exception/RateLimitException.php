<?php

namespace ListenNotes\PodcastApi\Exception;

class RateLimitException extends ListenApiException
{
    const STATUS = 429;
    // Too many requests made to the API too quickly
}
