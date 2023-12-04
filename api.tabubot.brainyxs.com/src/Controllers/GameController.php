<?php

namespace tabubotapi\Controllers;

use Schneidermanuel\Dynalinker\Controller\HttpGet;
use tabubotapi\Core\Authenticator;
use tabubotapi\Core\EventSource\Event;

class GameController
{
    #[HttpGet("[a-zA-Z0-9]{4}/events/.*")]
    public function MainEventStream($code, $otp)
    {
        $playerEntity = Authenticator::Redeem($otp);
        Event::StartSource();
    }
}