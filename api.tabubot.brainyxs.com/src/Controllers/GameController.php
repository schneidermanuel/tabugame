<?php

namespace tabubotapi\Controllers;

use Schneidermanuel\Dynalinker\Controller\HttpGet;
use Schneidermanuel\Dynalinker\Controller\HttpPost;
use Schneidermanuel\Dynalinker\Core\Dynalinker;
use tabubotapi\Core\Authenticator;
use tabubotapi\Core\EventSource\Event;
use tabubotapi\Core\Game\InitDataGenerator;
use tabubotapi\Core\Response;
use tabubotapi\Entities\CardEntity;
use tabubotapi\Entities\GameActionEntity;
use tabubotapi\Entities\GameEntity;
use tabubotapi\Entities\PlayerEntity;

class GameController
{
    private $logStore;
    private $cardStore;
    private $gameStore;
    private $playerStore;

    public function __construct()
    {
        $dynalinker = Dynalinker::Get();
        $this->logStore = $dynalinker->CreateStore(GameActionEntity::class);
        $this->cardStore = $dynalinker->CreateStore(CardEntity::class);
        $this->gameStore = $dynalinker->CreateStore(GameEntity::class);
        $this->playerStore = $dynalinker->CreateStore(PlayerEntity::class);
    }

    #[HttpPost("[a-zA-Z0-9]{4}/stopTurn")]
    public function CompleteTimer($code)
    {
        if (!Authenticator::IsAuthenticated()) {
            Response::Send("User is not logged in", "ERROR");
        }
        list($game, $player) = $this->getPlayerGame($code);
        $timerStartFilter = new GameActionEntity();
        $timerStartFilter->GameId = $game->Id;
        $timerStartFilter->AdditionalData = $_POST["TimerTimestamp"];
        $timerStartFilter->EventType = "TIMERSTART";
        $timerStartQuery = $this->logStore->LoadWithFilter($timerStartFilter);
        if (count($timerStartQuery) < 1) {
            Response::Send("Invalid State", "ERROR");
        }
        $timerStartEntity = $timerStartQuery[0];

        $timerEndFilter = new GameActionEntity();
        $timerEndFilter->GameId = $game->Id;
        $timerEndFilter->AdditionalData = $timerStartEntity->Id;
        $timerEndFilter->EventType = "TIMEREND";
        $timerEndQuery = $this->logStore->LoadWithFilter($timerEndFilter);
        if (count($timerEndQuery) > 0) {
            Response::Send("Timer already ended", "INFO");
        }

        $timerEndEntity = new GameActionEntity();
        $timerEndEntity->AdditionalData = $timerStartEntity->Id;
        $timerEndEntity->GameId = $game->Id;
        $timerEndEntity->EventType = "TIMEREND";
        $timerEndEntity->PlayerId = $timerStartEntity->PlayerId;
        $this->logStore->SaveOrUpdate($timerEndEntity);
        $this->SetNewPlayerActive($game);
        Response::Send(new \stdClass());


    }

    private function SetNewPlayerActive($gameEntity)
    {
        $maxTurnstart = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE type = 'TURNSTART' AND gameId = $gameEntity->Id ORDER BY gameActionLogId DESC LIMIT 1")[0];
        $currentPlayerEntity = $this->playerStore->LoadById($maxTurnstart->PlayerId);
        $currentTeam = $currentPlayerEntity->Team;
        $nextTeam = "blue";
        if ($currentTeam == "blue") {
            $nextTeam = "red";
        }

        $nextPlayerQuery = $this->playerStore->CustomQuery(
            "SELECT *
FROM player
where playerId IN
      (SELECT p.playerId
       FROM player p
                LEFT JOIN gameActionLog turnstart
                          ON p.playerId = turnstart.relevantPlayer
                              AND turnstart.type = 'TURNSTART'
       WHERE p.gameId = $gameEntity->Id
         AND team = '$nextTeam'
       GROUP BY p.playerId
       ORDER BY MAX(turnstart.gameActionLogId))
LIMIT 1;"
        );
        $nextPlayer = $nextPlayerQuery[0];
        $turnLog = new GameActionEntity();
        $turnLog->EventType = "TURNSTART";
        $turnLog->GameId = $gameEntity->Id;
        $turnLog->PlayerId = $nextPlayer->Id;
        $this->logStore->SaveOrUpdate($turnLog);
    }


    #[HttpGet("[a-zA-Z0-9]{4}/events/.*")]
    public function MainEventStream($code, $otp)
    {
        Event::StartSource();
        $playerEntity = Authenticator::Redeem($otp);
        $initData = (new InitDataGenerator())->GetInitData($playerEntity);
        $maxTimerstarts = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE type = 'TIMERSTART' AND gameId = $playerEntity->GameId ORDER BY gameActionLogId DESC LIMIT 1");
        if (count($maxTimerstarts) > 0) {
            $initData->Timestamp = $maxTimerstarts[0]->AdditionalData;
        }
        $maxTurnstart = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE type = 'TURNSTART' AND gameId = $playerEntity->GameId ORDER BY gameActionLogId DESC LIMIT 1");
        $initData->IsMyTurn = $maxTurnstart[0]->PlayerId == $playerEntity->Id;
        $maxCardDisplay = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE type = 'CARDDISPLAY' AND gameId = $playerEntity->GameId ORDER BY gameActionLogId DESC LIMIT 1");
        if (count($maxCardDisplay) > 0) {
            $cardDisplay = new \stdClass();
            $cardEntity = $this->cardStore->LoadById($maxCardDisplay[0]->AdditionalData);
            $cardDisplay->CardWord = $cardEntity->Text;
            $cardDisplay->Word1 = $cardEntity->Keyword1;
            $cardDisplay->Word2 = $cardEntity->Keyword2;
            $cardDisplay->Word3 = $cardEntity->Keyword3;
            $cardDisplay->Word4 = $cardEntity->Keyword4;
            $initData->Card = $cardDisplay;
        }
        Event::SendData($initData, "INIT");
        $maxTurnstart = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE type = 'TURNSTART' AND gameId = $playerEntity->GameId ORDER BY gameActionLogId DESC LIMIT 1")[0];
        $turnstart = new \stdClass();
        $turnstart->PlayerId = $maxTurnstart->PlayerId;
        $turnstart->IsMyTurn = $maxTurnstart->PlayerId == $playerEntity->Id;
        Event::SendData($turnstart, "TURNSTART");
        $maxLog = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE gameId = $playerEntity->GameId ORDER BY gameActionLogId DESC LIMIT 1")[0];
        $maxLogId = $maxLog->Id;
        $blueScore = $this->GetTeamScore($playerEntity->GameId, 'blue');
        $redScore = $this->GetTeamScore($playerEntity->GameId, 'red');
        $newScores = new \stdClass();
        $newScores->Blue = $blueScore;
        $newScores->Red = $redScore;
        Event::SendData($newScores, "SCORES");
        while (!connection_aborted()) {
            $logQuery = "SELECT * FROM gameActionLog WHERE gameId = $playerEntity->GameId AND gameActionLogId > $maxLogId ORDER BY gameActionLogId";
            $newLogs = $this->logStore->CustomQuery($logQuery);
            foreach ($newLogs as $newLog) {
                $maxLogId = $newLog->Id;
                if ($newLog->EventType == "TURNSTART") {
                    $turnstart = new \stdClass();
                    $turnstart->PlayerId = $newLog->PlayerId;
                    $turnstart->IsMyTurn = $newLog->PlayerId == $playerEntity->Id;
                    Event::SendData($turnstart, "TURNSTART");
                }
                if ($newLog->EventType == "TIMERSTART") {
                    $timerStart = new \stdClass();
                    $timerStart->Timestamp = $newLog->AdditionalData;
                    Event::SendData($timerStart, "TIMERSTART");
                }
                if ($newLog->EventType == "CARDDISPLAY") {
                    $cardDisplay = new \stdClass();
                    $cardEntity = $this->cardStore->LoadById($newLog->AdditionalData);
                    $cardDisplay->CardWord = $cardEntity->Text;
                    $cardDisplay->Word1 = $cardEntity->Keyword1;
                    $cardDisplay->Word2 = $cardEntity->Keyword2;
                    $cardDisplay->Word3 = $cardEntity->Keyword3;
                    $cardDisplay->Word4 = $cardEntity->Keyword4;
                    Event::SendData($cardDisplay, "CARDDISPLAY");
                }
                if ($newLog->EventType == "CARDACK") {
                    $blueScore = $this->GetTeamScore($playerEntity->GameId, 'blue');
                    $redScore = $this->GetTeamScore($playerEntity->GameId, 'red');
                    $newScores = new \stdClass();
                    $newScores->Blue = $blueScore;
                    $newScores->Red = $redScore;
                    Event::SendData($newScores, "SCORES");
                }

            }
            Event::SendData("PING", "IGNORE");
            sleep(1);
        }
    }

    private function GetTeamScore($gameId, $team)
    {
        $filter = new GameActionEntity();
        $filter->GameId = $gameId;
        $filter->AdditionalData = $team;
        $filter->EventType = "CARDACK";
        $query = $this->logStore->LoadWithFilter($filter);
        return count($query);
    }

    #[HttpPost("[a-zA-Z0-9]{4}/cardSkip")]
    public function CardSkip($code)
    {
        list($game, $player) = $this->getPlayerGame($code);
        $maxTurnstart = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE type = 'TURNSTART' AND gameId = $player->GameId ORDER BY gameActionLogId DESC LIMIT 1")[0];
        if ($maxTurnstart->PlayerId != $player->Id) {
            Response::Send("It's not your turn", "ERROR");
        }
        $newerTimerStarts = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE type = 'TIMERSTART' and gameId = $player->GameId AND gameActionLogId > $maxTurnstart->Id");
        if (count($newerTimerStarts) < 1) {
            Response::Send("The turn did not start correctly", "ERROR");
        }
        $newTimerStartId = $newerTimerStarts[0]->Id;
        $timerCompleteQuery = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE type = 'TIMEREND' and additionalData = $newTimerStartId");
        if (count($timerCompleteQuery) > 0) {
            Response::Send("The turn is already completed", "ERROR");
        }
        $pointLog = new GameActionEntity();
        $pointLog->PlayerId = $player->Id;
        $pointLog->GameId = $game->Id;
        $pointLog->EventType = "CARDSKIP";
        $pointLog->AdditionalData = $player->Team;
        $this->logStore->SaveOrUpdate($pointLog);

        $card = $this->GetANewCardForTeam($player);
        if (isset($card)) {
            $cardDisplayLog = new GameActionEntity();
            $cardDisplayLog->GameId = $game->Id;
            $cardDisplayLog->PlayerId = $player->Id;
            $cardDisplayLog->EventType = "CARDDISPLAY";
            $cardDisplayLog->AdditionalData = $card->Id;
            $this->logStore->SaveOrUpdate($cardDisplayLog);
        }

        Response::Send("Card skipped!", "WARNING");
    }

    #[HttpPost("[a-zA-Z0-9]{4}/cardCorrect")]
    public function CardCorrect($code)
    {
        list($game, $player) = $this->getPlayerGame($code);
        $maxTurnstart = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE type = 'TURNSTART' AND gameId = $player->GameId ORDER BY gameActionLogId DESC LIMIT 1")[0];
        if ($maxTurnstart->PlayerId != $player->Id) {
            Response::Send("It's not your turn", "ERROR");
        }
        $newerTimerStarts = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE type = 'TIMERSTART' and gameId = $player->GameId AND gameActionLogId > $maxTurnstart->Id");
        if (count($newerTimerStarts) < 1) {
            Response::Send("The turn did not start correctly", "ERROR");
        }
        $newTimerStartId = $newerTimerStarts[0]->Id;
        $timerCompleteQuery = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE type = 'TIMEREND' and additionalData = $newTimerStartId");
        if (count($timerCompleteQuery) > 0) {
            Response::Send("The turn is already completed", "ERROR");
        }
        $pointLog = new GameActionEntity();
        $pointLog->PlayerId = $player->Id;
        $pointLog->GameId = $game->Id;
        $pointLog->EventType = "CARDACK";
        $pointLog->AdditionalData = $player->Team;
        $this->logStore->SaveOrUpdate($pointLog);

        $card = $this->GetANewCardForTeam($player);
        if (isset($card)) {
            $cardDisplayLog = new GameActionEntity();
            $cardDisplayLog->GameId = $game->Id;
            $cardDisplayLog->PlayerId = $player->Id;
            $cardDisplayLog->EventType = "CARDDISPLAY";
            $cardDisplayLog->AdditionalData = $card->Id;
            $this->logStore->SaveOrUpdate($cardDisplayLog);
        }

        Response::Send("Card completed!");

    }

    #[HttpPost("[a-zA-Z0-9]{4}/startturn")]
    public function StartTurn($code)
    {
        list($game, $player) = $this->getPlayerGame($code);
        $maxTurnstart = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE type = 'TURNSTART' AND gameId = $player->GameId ORDER BY gameActionLogId DESC LIMIT 1")[0];
        $isMyTurn = $maxTurnstart->PlayerId == $player->Id;
        if (!$isMyTurn) {
            Response::Send("It's not your turn", "ERROR");
            return;
        }
        $newerTimerStarts = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE type = 'TIMERSTART' and gameId = $player->Id AND gameActionLogId > $maxTurnstart->Id");
        if (count($newerTimerStarts) > 0) {
            Response::Send("Turn already started", "ERROR");
        }
        $card = $this->GetANewCardForTeam($player);
        $timestamp = time();
        $startTurnLog = new GameActionEntity();
        $startTurnLog->PlayerId = $player->Id;
        $startTurnLog->GameId = $game->Id;
        $startTurnLog->AdditionalData = $timestamp;
        $startTurnLog->EventType = "TIMERSTART";
        $this->logStore->SaveOrUpdate($startTurnLog);
        $cardDisplayLog = new GameActionEntity();
        $cardDisplayLog->GameId = $game->Id;
        $cardDisplayLog->PlayerId = $player->Id;
        $cardDisplayLog->EventType = "CARDDISPLAY";
        $cardDisplayLog->AdditionalData = $card->Id;
        $this->logStore->SaveOrUpdate($cardDisplayLog);
        Response::Send("turn started");
    }

    private function GetANewCardForTeam($playerEntity)
    {
        $gameId = $playerEntity->GameId;
        $game = $this->gameStore->LoadById($gameId);

        $cardList = $this->cardStore->CustomQuery("SELECT c.*
FROM card c
WHERE c.cardSetId = $game->cardSetId
  AND c.cardId NOT IN (SELECT gal.additionalData
                       FROM gameActionLog gal
                                JOIN player pl ON gal.relevantPlayer = pl.playerId
                       WHERE gal.type = 'CARDDISPLAY'
                         AND pl.team = '$playerEntity->Team'
                         AND pl.gameId = $playerEntity->GameId)
ORDER BY rand()
LIMIT 1");

        if (count($cardList) == 0) {
            return null;
        }

        return $cardList[0];
    }

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

}