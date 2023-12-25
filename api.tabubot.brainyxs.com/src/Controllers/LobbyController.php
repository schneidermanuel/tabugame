<?php

namespace tabubotapi\Controllers;

use Schneidermanuel\Dynalinker\Controller\HttpGet;
use Schneidermanuel\Dynalinker\Controller\HttpPost;
use Schneidermanuel\Dynalinker\Core\Dynalinker;
use tabubotapi\Core\Api\DiscordApiHelper;
use tabubotapi\Core\Authenticator;
use tabubotapi\Core\EventSource\Event;
use tabubotapi\Core\Game\InitDataGenerator;
use tabubotapi\Core\Game\PlayerAdder;
use tabubotapi\Core\Response;
use tabubotapi\Entities\CardSetEntity;
use tabubotapi\Entities\GameActionEntity;
use tabubotapi\Entities\GameEntity;
use tabubotapi\Entities\PlayerEntity;

class LobbyController
{
    private $dynalinker;
    private $gameStore;
    private $cardSetStore;
    private $playerStore;
    private $apiHelper;
    private $logStore;

    public function __construct()
    {
        $this->dynalinker = Dynalinker::Get();
        $this->gameStore = $this->dynalinker->CreateStore(GameEntity::class);
        $this->cardSetStore = $this->dynalinker->CreateStore(CardSetEntity::class);
        $this->playerStore = $this->dynalinker->CreateStore(PlayerEntity::class);
        $this->apiHelper = new DiscordApiHelper();
        $this->logStore = $this->dynalinker->CreateStore(GameActionEntity::class);
    }

    #[HttpPost("create")]
    public function CreateNewGame()
    {
        if (!Authenticator::IsAuthenticated()) {
            http_response_code(401);
            die();
        }
        $tabuSetId = $_POST['SET_ID'];
        if (!isset($tabuSetId)) {
            http_response_code(400);
            die();
        }
        $cardSet = $this->cardSetStore->LoadById($tabuSetId);
        if (!isset($cardSet)) {
            http_response_code(400);
            die();
        }

        $gameCode = $this->gameCode();

        $game = new GameEntity();
        $game->created = date('Y-m-d H:i:s', time());
        $game->State = 'OPEN';
        $game->cardSetId = $cardSet->Id;
        $game->Code = $gameCode;
        $gameId = $this->gameStore->SaveOrUpdate($game);
        $adder = new PlayerAdder();
        $adder->AddCurrentUserToGame($gameId);

        $result = new \stdClass();
        $result->Game = $game->Code;
        echo json_encode($result);

    }

    #[HttpPost("[a-zA-Z0-9]{4}/start")]
    function StartGame($gameCode)
    {
        list($game, $playerEntity) = $this->getPlayerGame($gameCode);
        if (!$playerEntity->IsHost) {
            Response::Send("Only the Host can start a Game", "ERROR");
            die();
        }
        $redPlayersFilter = new PlayerEntity();
        $redPlayersFilter->GameId = $game->Id;
        $redPlayersFilter->Team = "red";
        $redPlayers = $this->playerStore->LoadWithFilter($redPlayersFilter);
        $bluePlayersFilter = new PlayerEntity();
        $bluePlayersFilter->GameId = $game->Id;
        $bluePlayersFilter->Team = "blue";
        $bluePlayers = $this->playerStore->LoadWithFilter($bluePlayersFilter);
//        if (count($bluePlayers) <= 1 || count($redPlayers) <= 1) {
//            Response::Send("Both Teams need at least 2 players", "ERROR");
//            die();
//        }
        $game->State = 'GAME';
        $this->gameStore->SaveOrUpdate($game);
        $log = new GameActionEntity();
        $log->GameId = $game->Id;
        $log->PlayerId = $playerEntity->Id;
        $log->EventType = "STARTGAME";
        $this->logStore->SaveOrUpdate($log);

        $firstPlayerFilter = new PlayerEntity();
        $firstPlayerFilter->GameId = $game->Id;
        $firstPlayerFilter->Team = "blue";
        $firstPlayerList = $this->playerStore->LoadWithFilter($firstPlayerFilter);
        $firstPlayer = $firstPlayerList[0];
        $turnLog = new GameActionEntity();
        $turnLog->EventType = "TURNSTART";
        $turnLog->GameId = $game->Id;
        $turnLog->PlayerId = $firstPlayer->Id;
        $this->logStore->SaveOrUpdate($turnLog);

        Response::Send("Game started");
    }

    function gameCode(): string
    {
        $guid = sprintf('%04X', mt_rand(0, 65535));
        $gamesFilter = new GameEntity();
        $gamesFilter->Code = $guid;
        $gamesFilter->State = "OPEN";
        $conflictingGames = $this->gameStore->LoadWithFilter($gamesFilter);
        if (count($conflictingGames) != 0) {
            return $this->gameCode();
        }
        return $guid;
    }

    #[HttpGet("[a-zA-Z0-9]{4}")]
    public function GetGame($gameCode)
    {
        if (!Authenticator::IsAuthenticated()) {
            http_response_code(401);
            die();
        }

        $gameFilter = new GameEntity();
        $gameFilter->Code = $gameCode;
        $gameFilter->State = "OPEN";
        $games = $this->gameStore->LoadWithFilter($gameFilter);
        $result = new \stdClass();
        $result->canJoin = count($games) == 1;
        echo json_encode($result);
    }

    #[HttpGet("[a-zA-Z0-9]{4}/state")]
    public function GetState($gameCode)
    {
        list($game, $playerEntity) = $this->getPlayerGame($gameCode);
        $playersInGameFilter = new PlayerEntity();
        $playersInGameFilter->GameId = $game->Id;
        $playersInGame = $this->playerStore->LoadWithFilter($playersInGameFilter);
        $result = new \stdClass();
        $result->State = $game->State;
        $result->Players = array();
        $result->IsHost = $playerEntity->IsHost;
        foreach ($playersInGame as $playerInGame) {
            $playerResult = new \stdClass();
            $dcUser = json_decode($this->apiHelper->GetWithBotAutherization("https://discord.com/api/v10/users/$playerInGame->DcId"));
            $avatar_url = $this->apiHelper->GetAvatarUrl($dcUser);
            $playerResult->PbUrl = $avatar_url;
            $playerResult->Username = $dcUser->global_name;
            $playerResult->Team = $playerInGame->Team;

            $result->Players[] = $playerResult;
        }

        echo json_encode($result);
    }

    #[HttpPost("[a-zA-Z0-9]{4}/join")]
    public function JoinGame($gameCode)
    {
        if (!Authenticator::IsAuthenticated()) {
            http_response_code(401);
            die();
        }
        $game = $this->GetGameByCode($gameCode);
        $adder = new PlayerAdder();
        $adder->AddCurrentUserToGame($game->Id);

        Response::Send("Joined Game");

    }

    #[HttpPost("[a-zA-Z0-9]{4}/newTeams")]
    public function SendTeams($gameCode)
    {
        list($game, $playerEntity) = $this->getPlayerGame($gameCode);
        if (!$playerEntity->IsHost) {
            Response::Send("Only the Host can start a Game", "ERROR");
            die();
        }
        foreach ($_POST as $player) {
            $entity = $this->playerStore->LoadById($player["Id"]);
            $newTeam = $player["Team"];
            $currentTeam = $entity->Team;
            if ($newTeam != $currentTeam) {
                $log = new GameActionEntity();
                $log->GameId = $playerEntity->GameId;
                $log->EventType = "TEAMCHANGE";
                $log->AdditionalData = $newTeam;
                $log->PlayerId = $player["Id"];
                $this->logStore->SaveOrUpdate($log);
                $entity->Team = $newTeam;
                $this->playerStore->SaveOrUpdate($entity);
            }
        }
        Response::Send("Teams updated");
    }

    #[HttpGet("[a-zA-Z0-9]{4}/otp")]
    public function GenerateOtp($gameCode)
    {
        list($game, $playerEntity) = $this->getPlayerGame($gameCode);
        echo Authenticator::GenerateOtpCode($playerEntity->Id);
    }

    #[HttpGet("[a-zA-Z0-9]{4}/lobby/.*")]
    public function LobbyEventStream($gameCode, $otp)
    {
        Event::StartSource();
        $playerEntity = Authenticator::Redeem($otp);
        $initData = (new InitDataGenerator())->GetInitData($playerEntity);
        Event::SendData($initData, "INIT");
        $maxLogId = $this->dynalinker->Query("SELECT MAX(gameActionLogId) as m FROM gameActionLog WHERE gameId = $playerEntity->GameId")[0]['m'];
        while (!connection_aborted()) {
            $logQuery = "SELECT * FROM gameActionLog WHERE gameId = $playerEntity->GameId AND gameActionLogId > $maxLogId ORDER BY gameActionLogId";
            $newLogs = $this->logStore->CustomQuery($logQuery);
            foreach ($newLogs as $newLog) {
                $maxLogId = $newLog->Id;
                if ($newLog->EventType == "JOIN") {
                    $player = $this->playerStore->LoadById($newLog->PlayerId);
                    $newPlayer = new \stdClass();
                    $newPlayer->Id = $newLog->PlayerId;
                    $newPlayer->Name = $player->Name;
                    $dcUser = json_decode($this->apiHelper->GetWithBotAutherization("https://discord.com/api/v10/users/$player->DcId"));
                    $avatar_url = $this->apiHelper->GetAvatarUrl($dcUser);
                    $newPlayer->ImageUrl = $avatar_url;
                    $newPlayer->IsHost = $player->IsHost;
                    $newPlayer->Team = $player->Team;
                    Event::SendData($newPlayer, "JOINED");
                }
                if ($newLog->EventType == "TEAMCHANGE") {
                    $data = new \stdClass();
                    $data->Player = $newLog->PlayerId;
                    $data->Team = $newLog->AdditionalData;
                    Event::SendData($data, "TEAMCHANGE");
                }
                if ($newLog->EventType == "STARTGAME") {
                    Event::SendData("LETS A GO", "STARTGAME");
                }


            }
            Event::SendData("PING", "IGNORE");
            sleep(3);
        }
    }

    private function GetGameByCode($gameCode)
    {
        $gameFilter = new GameEntity();
        $gameFilter->Code = $gameCode;
        $gameFilter->State = 'OPEN';
        $games = $this->gameStore->LoadWithFilter($gameFilter);
        if (count($games) == 1) {
            return $games[0];
        }
        $gameFilter = new GameEntity();
        $gameFilter->Code = $gameCode;
        $gameFilter->State = 'GAME';
        $games = $this->gameStore->LoadWithFilter($gameFilter);
        if (count($games) == 1) {
            return $games[0];
        }
        return null;
    }

    /**
     * @param $gameCode
     * @return array|void
     */
    public function getPlayerGame($gameCode)
    {
        if (!Authenticator::IsAuthenticated()) {
            http_response_code(401);
            die();
        }
        $user = Authenticator::GetUser();
        $game = $this->GetGameByCode($gameCode);
        if ($game == null) {
            http_response_code(400);
            die();
        }
        $userId = $user->sub;
        $gameId = $game->Id;
        $playerFilter = new PlayerEntity();
        $playerFilter->GameId = $gameId;
        $playerFilter->DcId = $userId;
        $playerEntities = $this->playerStore->LoadWithFilter($playerFilter);
        if (count($playerEntities) != 1) {
            http_response_code(400);
            die();
        }
        $playerEntity = $playerEntities[0];
        return array($game, $playerEntity);
    }
}