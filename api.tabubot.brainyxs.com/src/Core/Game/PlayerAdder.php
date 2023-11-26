<?php

namespace tabubotapi\Core\Game;

use Schneidermanuel\Dynalinker\Core\Dynalinker;
use tabubotapi\Core\Authenticator;
use tabubotapi\Core\Response;
use tabubotapi\Entities\GameActionEntity;
use tabubotapi\Entities\GameEntity;
use tabubotapi\Entities\PlayerEntity;

class PlayerAdder
{
    private $dynalinker;
    private $playerStore;
    private $gameStore;
    private $logStore;

    public function __construct()
    {
        $this->dynalinker = Dynalinker::Get();
        $this->playerStore = $this->dynalinker->CreateStore(PlayerEntity::class);
        $this->gameStore = $this->dynalinker->CreateStore(GameEntity::class);
        $this->logStore = $this->dynalinker->CreateStore(GameActionEntity::class);
    }

    public function AddCurrentUserToGame($gameId)
    {
        $user = Authenticator::GetUser();
        $userId = $user->sub;

        $filter = new PlayerEntity();
        $filter->GameId = $gameId;
        $filter->DcId = $userId;
        $userInGame = $this->playerStore->LoadWithFilter($filter);
        if (count($userInGame) != 0) {
            Response::Send("Already in game", "ERROR");
            die();
        }
        $game = $this->gameStore->LoadById($gameId);
        if (!isset($game) || $game->State != 'OPEN') {
            Response::Send("Game not found", "ERROR");
            die();
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
        $player->DcId = $userId;
        $player->GameId = $gameId;
        $player->Name = $user->username;
        $player->Team = $teamToJoin;
        $player->IsHost = $host;

        $id = $this->playerStore->SaveOrUpdate($player);
        $log = new GameActionEntity();
        $log->PlayerId = $id;
        $log->EventType = "JOIN";
        $log->GameId = $gameId;
        $this->logStore->SaveOrUpdate($log);
    }

    private function GetTeamToJoin(int $bluePlayers, int $redPlayers): string
    {
        if ($bluePlayers <= $redPlayers) {
            return 'blue';
        }
        return 'red';
    }

    private function AmITheHost(int $blueCount, int $redCount)
    {
        if ($blueCount + $redCount == 0) {
            return true;
        }
        return 0;
    }
}