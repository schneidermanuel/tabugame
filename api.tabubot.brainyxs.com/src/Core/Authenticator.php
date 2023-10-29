<?php

namespace tabubotapi\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Authenticator
{
    public static function IsAuthenticated() : bool
    {
        $header = HeaderHelper::getHeader("Authorization");
        if (!isset($header))
        {
            return false;
        }
        $jwt = explode(" ", $header)[1];
        $decoded = JWT::decode($jwt, new Key($_ENV["JWT_SECRET"], "HS256"));
        if (!isset($decoded))
        {
            return false;
        }
        return true;
    }
    public static function GetUser() : ?\stdClass
    {
        if (!self::IsAuthenticated())
        {
            return null;
        }
        $header = HeaderHelper::getHeader("Authorization");
        $jwt = explode(" ", $header)[1];
        $decoded = JWT::decode($jwt, new Key($_ENV["JWT_SECRET"], "HS256"));
        return $decoded;
    }
}