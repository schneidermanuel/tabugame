<?php

namespace tabubotapi\Controllers;

use Firebase\JWT\JWT;
use Schneidermanuel\Dynalinker\Controller\HttpGet;

class AuthenticationController
{
    #[HttpGet("callback")]
    public function CodeCallback()
    {
        try {

            $code = $_GET["code"];
            $token = $this->processCode($code);
            $jwt = $this->generateJwt($token->access_token);
            $this->returnResult($jwt);
        } catch (\Exception) {
            $this->returnResult();
        }
    }

    /**
     * @param mixed $code
     * @return tokenObject
     */
    public function processCode(string $code)
    {
        $url = 'https://discord.com/api/v10/oauth2/token';
        $data = [
            "grant_type" => "authorization_code",
            "code" => $code,
            "redirect_uri" => "https://" . $_SERVER["SERVER_NAME"] . "/auth/callback",
            "client_id" => $_ENV["DISCORD_CLIENT_ID"],
            "client_secret" => $_ENV["DISCORD_CLIENT_SECRET"]
        ];
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];


        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        preg_match('/([0-9])\d+/', $http_response_header[0], $matches);
        $responsecode = intval($matches[0]);
        if ($responsecode != 200) {
            $this->returnResult();
            die();
        }
        $result = json_decode($result);
        return $result;
    }

    /**
     * @param $token
     * @return string
     */
    private function generateJwt($token): string
    {
        $url = 'https://discord.com/api/v10/oauth2/@me';
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Bearer $token",
            ],
        ];


        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result);
        $key = $_ENV["JWT_SECRET"];
        $displayName = $result->user->global_name;
        $userId = $result->user->id;
        $avatar_url = "https://cdn.discordapp.com/avatars/" . $result->user->id . "/" . $result->user->avatar . ".png?size=4096";
        $payload = [
            'iss' => "schneidermanuel",
            'iat' => time(),
            'sub' => $userId,
            'username' => $displayName,
            'pburl' => $avatar_url,
            'apitoken' => $token
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }

    /**
     * @param string|null $jwt
     * @return void
     */
    public function returnResult(string $jwt = null): void
    {
        if (isset($jwt)) {
            header("Location: " . $_ENV["FRONTEND_URL"] . "/authenticated/" . $jwt);
        } else {
            header("Location: " . $_ENV["FRONTEND_URL"] . "/error");
        }
    }
}