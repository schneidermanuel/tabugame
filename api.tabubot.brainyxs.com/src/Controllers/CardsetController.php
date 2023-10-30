<?php

namespace tabubotapi\Controllers;

use Schneidermanuel\Dynalinker\Controller\HttpGet;
use Schneidermanuel\Dynalinker\Core\Dynalinker;
use tabubotapi\Core\Api\DiscordApiHelper;
use tabubotapi\Entities\CardSetEntity;

class CardsetController
{

    #[HttpGet("id/.*")]
    public function LoadById($id)
    {
        $dynalinker = Dynalinker::Get();
        $store = $dynalinker->CreateStore(CardSetEntity::class);
        $entity = $store->LoadById($id);
        if ($entity == null) {
            echo json_encode(new \stdClass());
            return;
        }
        $ownerId = $entity->OwnerId;
        $api = new DiscordApiHelper();
        $user = json_decode($api->GetWithBotAutherization("https://discord.com/api/v10/users/$ownerId"));
        $displayName = $user->global_name;

        $result = new \stdClass();
        $result->Name = $entity->Name;
        $result->Id = $entity->Id;
        $result->Owner = $displayName;

        echo json_encode($result);
    }
}