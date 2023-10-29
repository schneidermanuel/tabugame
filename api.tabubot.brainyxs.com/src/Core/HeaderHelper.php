<?php

namespace tabubotapi\Core;

class HeaderHelper
{
    public static function getHeader($key)
    {
        $headers = apache_request_headers();
        $header = $headers[$key];
        return $header;
    }
}