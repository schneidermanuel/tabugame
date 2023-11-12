<?php

namespace tabubotapi\Core\Api;

use tabubotapi\Core\Authenticator;

class DiscordApiHelper
{
    public function GetAvatarUrl($response): string
    {

        if (!isset($response->avatar))
        {
            return "https://external-preview.redd.it/4PE-nlL_PdMD5PrFNLnjurHQ1QKPnCvg368LTDnfM-M.png?auto=webp&s=ff4c3fbc1cce1a1856cff36b5d2a40a6d02cc1c3";
        }
        $avatar_url = "https://cdn.discordapp.com/avatars/" . $response->id . "/" . $response->avatar . ".png?size=4096";
        return $avatar_url;
    }

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