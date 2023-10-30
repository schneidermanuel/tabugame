<?php

namespace tabubotapi\Core\Api;

use tabubotapi\Core\Authenticator;

class DiscordApiHelper
{
    public function GetWithAutherization($url)
    {
        $user = Authenticator::GetUser();
        if (!isset($user)) {
            http_response_code(401);
            die();
        }
        $token = $user->apitoken;
        $options = array(
            'http' => array(
                'method' => 'GET',
                'header' => "Content-Type: application/json\r\n
                Autherization: Bearer $token"
            )
        );
        $context = stream_context_create($options);
        $results = file_get_contents($url, false, $context);
        return $results;
    }

    public function GetWithBotAutherization($url)
    {
        $token = $_ENV["BOT_TOKEN"];
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Bot $token",
            ],
        ];
        $context = stream_context_create($options);
        $results = file_get_contents($url, false, $context);
        return $results;
    }
}