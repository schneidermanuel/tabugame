<?php

namespace tabubotapi\Controllers;

use Schneidermanuel\Dynalinker\Controller\HttpPost;
use Schneidermanuel\Dynalinker\Core\Dynalinker;
use tabubotapi\Core\Authenticator;
use tabubotapi\Core\Game\PlayerAdder;
use tabubotapi\Entities\CardSetEntity;
use tabubotapi\Entities\GameEntity;

class GameController
{
    private $dynalinker;
    private $gameStore;
    private $cardSetStore;

    public function __construct()
    {
        $this->dynalinker = Dynalinker::Get();
        $this->gameStore = $this->dynalinker->CreateStore(GameEntity::class);
        $this->cardSetStore = $this->dynalinker->CreateStore(CardSetEntity::class);
    }

    #[HttpPost("create")]
    public function CreateNewGame()
    {
        if (!Authenticator::IsAuthenticated()) {
            http_response_code(401);
            die();
        }
        $user = Authenticator::GetUser();
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

    #[HttpGet(".*/eventsource")]
    public function EventSource($gameId)
    {

    }
}