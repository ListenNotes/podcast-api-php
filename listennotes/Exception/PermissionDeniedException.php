<?php

declare(strict_types=1);

namespace ListenNotes\PodcastApi\Exception;

class PermissionDeniedException extends ListenApiException
{
    public const STATUS = 403;
}
