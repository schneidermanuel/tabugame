<?php

namespace tabubotapi\Core\Game;

use Schneidermanuel\Dynalinker\Core\Dynalinker;
use tabubotapi\Core\Authenticator;
use tabubotapi\Entities\GameEntity;
use tabubotapi\Entities\PlayerEntity;

class PlayerAdder
{
    private $dynalinker;
    private $playerStore;
    private $gameStore;

    public function __construct()
    {
        $this->dynalinker = Dynalinker::Get();
        $this->playerStore = $this->dynalinker->CreateStore(PlayerEntity::class);
        $this->gameStore = $this->dynalinker->CreateStore(GameEntity::class);
    }

    public function AddCurrentUserToGame($gameId)
    {
        $user = Authenticator::GetUser();
        $userId = $user->sub;

        $filter = new PlayerEntity();
        $filter->GameId = $gameId;
        $filter->DdId = $userId;
        $userInGame = $this->playerStore->LoadWithFilter($filter);
        if (count($userInGame) != 0) {
            return;
        }
        $game = $this->gameStore->LoadById($gameId);
        if (!isset($game) || $game->State != 'OPEN') {
            return;
        }

        $bluePlayerFilter = new PlayerEntity();
        $bluePlayerFilter->Team = 'blue';
        $bluePlayerFilter->GameId = $gameId;

        $bluePlayers = $this->playerStore->LoadWithFilter($bluePlayerFilter);

        $redPlayerFilter = new PlayerEntity();
        $redPlayerFilter->Team = 'red';
        $redPlayerFilter->GameId = $gameId;

        $redPlayers = $this->playerStore->LoadWithFilter($redPlayerFilter);

        $teamToJoin = $this->GetTeamToJoin(count($bluePlayers), count($redPlayers));
        $host = $this->AmITheHost(count($bluePlayers), count($redPlayers));

        $player = new PlayerEntity();
        $player->DdId = $userId;
        $player->GameId = $gameId;
        $player->Name = $user->username;
        $player->Team = $teamToJoin;
        $player->IsHost = $host;

        $this->playerStore->SaveOrUpdate($player);
    }

    private function GetTeamToJoin(int $bluePlayers, int $redPlayers): string
    {
        if ($bluePlayers >= $redPlayers) {
            return 'blue';
        }
        return 'red';
    }

    private function AmITheHost(int $blueCount, int $redCount)
    {
        if ($blueCount + $redCount == 0) {
            return true;
        }
        return false;
    }
}