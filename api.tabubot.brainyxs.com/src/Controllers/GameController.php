<?php

namespace tabubotapi\Controllers;

use Schneidermanuel\Dynalinker\Controller\HttpGet;
use Schneidermanuel\Dynalinker\Core\Dynalinker;
use tabubotapi\Core\Authenticator;
use tabubotapi\Core\EventSource\Event;
use tabubotapi\Core\Game\InitDataGenerator;
use tabubotapi\Entities\GameActionEntity;
use tabubotapi\Entities\PlayerEntity;

class GameController
{
    private $logStore;

    public function __construct()
    {
        $dynalinker = Dynalinker::Get();
        $this->logStore = $dynalinker->CreateStore(GameActionEntity::class);
    }

    #[HttpGet("[a-zA-Z0-9]{4}/events/.*")]
    public function MainEventStream($code, $otp)
    {
        Event::StartSource();
        $playerEntity = Authenticator::Redeem($otp);
        $initData = (new InitDataGenerator())->GetInitData($playerEntity);
        Event::SendData($initData, "INIT");
        $maxTurnstart = $this->logStore->CustomQuery("SELECT * FROM gameActionLog WHERE type = 'TURNSTART' AND gameId = $playerEntity->GameId ORDER BY gameActionLogId DESC LIMIT 1")[0];
        $turnstart = new \stdClass();
        $turnstart->PlayerId = $maxTurnstart->PlayerId;
        $turnstart->IsMyTurn = $maxTurnstart->PlayerId == $playerEntity->Id;
        Event::SendData($turnstart, "TURNSTART");
        while (!connection_aborted()) {
            Event::SendData("PING", "IGNORE");
            sleep(3);
        }
    }

}