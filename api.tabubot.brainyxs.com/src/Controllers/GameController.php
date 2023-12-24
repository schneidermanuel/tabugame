<?php

namespace tabubotapi\Controllers;

use Schneidermanuel\Dynalinker\Controller\HttpGet;
use tabubotapi\Core\Authenticator;
use tabubotapi\Core\EventSource\Event;
use tabubotapi\Core\Game\InitDataGenerator;
use tabubotapi\Entities\PlayerEntity;

class GameController
{
    #[HttpGet("[a-zA-Z0-9]{4}/events/.*")]
    public function MainEventStream($code, $otp)
    {
        Event::StartSource();
        $playerEntity = Authenticator::Redeem($otp);
        $initData = (new InitDataGenerator())->GetInitData($playerEntity);
        Event::SendData($initData, "INIT");
        while (!connection_aborted()) {
            Event::SendData("PING", "IGNORE");
            sleep(3);
        }
    }

}