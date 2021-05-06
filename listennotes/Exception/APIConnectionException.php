<?php

namespace ListenNotes\PodcastApi\Exception;

class APIConnectionException extends ListenApiException
{
    const STATUS = 408;
    // Network communication with Listen API failed
}
