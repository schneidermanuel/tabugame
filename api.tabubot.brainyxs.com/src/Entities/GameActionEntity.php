<?php

namespace tabubotapi\Entities;

use Schneidermanuel\Dynalinker\Entity\Attribute\Entity;
use Schneidermanuel\Dynalinker\Entity\Attribute\Persist;
use Schneidermanuel\Dynalinker\Entity\Attribute\PrimaryKey;

#[Entity("gameActionLog")]
class GameActionEntity
{
    #[Persist("gameActionLogId")]
    #[PrimaryKey]
    public $Id;

    #[Persist("relevantPlayer")]
    public $PlayerId;

    #[Persist("type")]
    public $EventType;

    #[Persist("additionalData")]
    public $AdditionalData;

    #[Persist("gameId")]
    public $GameId;

}