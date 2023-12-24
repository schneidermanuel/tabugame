<?php

namespace tabubotapi\Core\Game;

use Schneidermanuel\Dynalinker\Core\Dynalinker;
use Schneidermanuel\Dynalinker\Entity\EntityStore;
use tabubotapi\Core\Api\DiscordApiHelper;
use tabubotapi\Entities\GameEntity;
use tabubotapi\Entities\PlayerEntity;

class InitDataGenerator
{
    private DiscordApiHelper $apiHelper;
    private EntityStore $playerStore;
    private Dynalinker $dynalinker;

    public function __construct()
    {
        $this->dynalinker = Dynalinker::Get();
        $this->playerStore = $this->dynalinker->CreateStore(PlayerEntity::class);
        $this->apiHelper = new DiscordApiHelper();
    }

    public function GetInitData(PlayerEntity $playerEntity): \stdClass
    {
        $playersFilter = new PlayerEntity();
        $playersFilter->GameId = $playerEntity->GameId;
        $players = $this->playerStore->LoadWithFilter($playersFilter);
        $initData = new \stdClass();
        $initData->Players = array();
        $initData->IsHost = $playerEntity->IsHost;
        foreach ($players as $player) {
            $newPlayer = new \stdClass();
            $newPlayer->Name = $player->Name;
            $dcUser = json_decode($this->apiHelper->GetWithBotAutherization("https://discord.com/api/v10/users/$player->DcId"));
            $avatar_url = $this->apiHelper->GetAvatarUrl($dcUser);
            $newPlayer->ImageUrl = $avatar_url;
            $newPlayer->IsHost = $player->IsHost;
            $newPlayer->Team = $player->Team;
            $newPlayer->Id = $player->Id;
            $initData->Players[] = $newPlayer;
        }
        return $initData;
    }
}