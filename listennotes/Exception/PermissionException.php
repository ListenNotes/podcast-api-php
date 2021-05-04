<?php

declare(strict_types=1);

namespace ListenNotes\PodcastApiClient\Exception;

class PermissionException extends \Exception
{
    public function getHttpStatus()
    {
        return 403;
    }

    public function getMessage()
    {
        return 'Unauthorized 403';
    }

    public function getJsonBody()
    {
        return json_decode( '{"message":"Unauthorized 403","status":"403"}' );
    }
}
