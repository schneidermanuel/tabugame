<?php

namespace tabubotapi\Entities;

use Schneidermanuel\Dynalinker\Entity\Attribute\Entity;
use Schneidermanuel\Dynalinker\Entity\Attribute\Persist;
use Schneidermanuel\Dynalinker\Entity\Attribute\PrimaryKey;

#[Entity("game")]
class GameEntity
{
    #[PrimaryKey]
    #[Persist("gameId")]
    public $Id;

    #[Persist("cardSet")]
    public $cardSetId;

    #[Persist("created")]
    public $created;

    #[Persist("state")]
    public $State;
    #[Persist("gameCode")]
    public $Code;
}
