<?php

namespace tabubotapi\Controllers;

use Schneidermanuel\Dynalinker\Controller\HttpGet;
use Schneidermanuel\Dynalinker\Controller\HttpPost;
use Schneidermanuel\Dynalinker\Core\Dynalinker;
use tabubotapi\Core\Api\DiscordApiHelper;
use tabubotapi\Core\Authenticator;
use tabubotapi\Core\EventSource\Event;
use tabubotapi\Core\Game\PlayerAdder;
use tabubotapi\Core\Response;
use tabubotapi\Entities\CardSetEntity;
use tabubotapi\Entities\GameEntity;
use tabubotapi\Entities\PlayerEntity;

class GameController
{
    private $dynalinker;
    private $gameStore;
    private $cardSetStore;
    private $playerStore;
    private $apiHelper;

    public function __construct()
    {
        $this->dynalinker = Dynalinker::Get();
        $this->gameStore = $this->dynalinker->CreateStore(GameEntity::class);
        $this->cardSetStore = $this->dynalinker->CreateStore(CardSetEntity::class);
        $this->playerStore = $this->dynalinker->CreateStore(PlayerEntity::class);
        $this->apiHelper = new DiscordApiHelper();
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
        if (count($bluePlayers) <= 1 || count($redPlayers) <= 1) {
            Response::Send("Both Teams need at least 2 players", "ERROR");
            die();
        }
        $game->State = 'GAME';
        $this->gameStore->SaveOrUpdate($game);
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
        $playersFilter = new PlayerEntity();
        $playersFilter->GameId = $playerEntity->GameId;
        $players = $this->playerStore->LoadWithFilter($playersFilter);
        $initData = new \stdClass();
        $initData->Players = array();
        $initData->IsHost = $playerEntity->IsHost;
        $displayedPlayersId = array();
        foreach ($players as $player) {
            $initPlayer = new \stdClass();
            $initPlayer->Name = $player->Name;
            $dcUser = json_decode($this->apiHelper->GetWithBotAutherization("https://discord.com/api/v10/users/$player->DcId"));
            $avatar_url = $this->apiHelper->GetAvatarUrl($dcUser);
            $initPlayer->ImageUrl = $avatar_url;
            $initPlayer->IsHost = $player->IsHost;
            $initPlayer->Team = $player->Team;
            $initData->Players[] = $initPlayer;
            $displayedPlayersId[] = $player->Id;
        }
        Event::SendData($initData, "INIT");
        while (!connection_aborted()) {
            $playersInGame = $this->playerStore->LoadWithFilter($playersFilter);
            foreach ($playersInGame as $p) {
                if (!in_array($p->Id, $displayedPlayersId)) {
                    $displayedPlayersId[] = $p->Id;
                    $initPlayer = new \stdClass();
                    $initPlayer->Name = $p->Name;
                    $dcUser = json_decode($this->apiHelper->GetWithBotAutherization("https://discord.com/api/v10/users/$p->DcId"));
                    $avatar_url = $this->apiHelper->GetAvatarUrl($dcUser);
                    $initPlayer->ImageUrl = $avatar_url;
                    $initPlayer->IsHost = $p->IsHost;
                    $initPlayer->Team = $p->Team;
                    Event::SendData($initPlayer, "JOINED");
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