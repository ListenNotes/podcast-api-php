<?php

namespace ListenNotes\PodcastApi\Exception;

class RateLimitException extends ListenApiException
{
    const STATUS = 429;
    // For FREE plan, exceeding the quota limit;
    // or for all plans, sending too many requests too fast and
    // exceeding the rate limit - https://www.listennotes.com/api/faq/#faq17
}
