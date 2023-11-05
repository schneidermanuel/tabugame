<?php

namespace tabubotapi\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Schneidermanuel\Dynalinker\Core\Dynalinker;
use tabubotapi\Entities\PlayerEntity;
use tabubotapi\Entities\SessionEntity;

class Authenticator
{
    public static function IsAuthenticated(): bool
    {
        $header = HeaderHelper::getHeader("Authorization");
        if (!isset($header)) {
            return false;
        }
        $jwt = explode(" ", $header)[1];
        $decoded = JWT::decode($jwt, new Key($_ENV["JWT_SECRET"], "HS256"));
        if (!isset($decoded)) {
            return false;
        }
        return true;
    }

    public static function GetUser(): ?\stdClass
    {
        if (!self::IsAuthenticated()) {
            return null;
        }
        $header = HeaderHelper::getHeader("Authorization");
        $jwt = explode(" ", $header)[1];
        $decoded = JWT::decode($jwt, new Key($_ENV["JWT_SECRET"], "HS256"));
        return $decoded;
    }

    public static function GenerateOtpCode($playerId): string
    {
        $dynalinker = Dynalinker::Get();
        $session = new SessionEntity();
        $session->PlayerId = $playerId;
        $session->GeneratedTime = time();
        $session->Identifier = sprintf('%04X%04X%04X%04X%04X%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
        $session->GeneratedTime = date('Y-m-d H:i:s', time());
        $sessionStore = $dynalinker->CreateStore(SessionEntity::class);
        $sessionStore->SaveOrUpdate($session);
        return $session->Identifier;
    }

    public static function GenerateIdentifier()
    {

    }

    public static function Redeem(string $otp): PlayerEntity
    {
        $sessionStore = Dynalinker::Get()->CreateStore(SessionEntity::class);
        $playerStore = Dynalinker::Get()->CreateStore(PlayerEntity::class);
        $filter = new SessionEntity();
        $filter->Identifier = $otp;
        $sessions = $sessionStore->LoadWithFilter($filter);
        if (count($sessions) != 1) {
            http_response_code(401);
            die();
        }
        $session = $sessions[0];
        $generatedDate = new \DateTime($session->GeneratedTime);
        $currentDateTime = new \DateTime('now');
        $currentDateTime->modify("- 10 seconds");
        if ($generatedDate < $currentDateTime) {
            http_response_code(400);
            die();
        }
        $sessionStore->DeleteById($session->Id);
        $playerEntity = $playerStore->LoadById($session->PlayerId);
        return $playerEntity;
    }
}