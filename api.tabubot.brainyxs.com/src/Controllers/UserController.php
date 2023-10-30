<?php

namespace tabubotapi\Controllers;

use Schneidermanuel\Dynalinker\Controller\HttpGet;
use Schneidermanuel\Dynalinker\Core\Dynalinker;
use tabubotapi\Core\Authenticator;
use tabubotapi\Entities\CardSetEntity;

class UserController
{
    #[HttpGet("me")]
    public function Me()
    {
        $user = Authenticator::GetUser();
        if (!isset($user)) {
            echo json_encode(array("authenticated" => false));
            die();
        }
        echo json_encode(array("authenticated" => true, "user" => $user));
    }

    #[HttpGet("sets")]
    public function GetSets()
    {
        $user = Authenticator::GetUser();
        if (!isset($user)) {
            http_response_code(401);
            die();
        }
        $dynalinker = Dynalinker::Get();
        $cardSetStore = $dynalinker->CreateStore(CardSetEntity::class);
        $filterEntity = new CardSetEntity();
        $filterEntity->OwnerId = $user->sub;
        $cardSets = $cardSetStore->LoadWithFilter($filterEntity);

        $results = array();
        foreach ($cardSets as $cardSet) {
            $results[] = array(
                "Id" => $cardSet->Id,
                "Name" => $cardSet->Name
            );
        }
        echo json_encode($results);
    }
}