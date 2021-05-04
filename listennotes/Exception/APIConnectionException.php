<?php

namespace ListenNotes\PodcastApiClient\Exception;

class APIConnectionException extends ListenApiException
{
    const STATUS = 408;
    // Network communication with Listen API failed
}
