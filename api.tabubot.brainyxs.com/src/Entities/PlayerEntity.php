<?php

namespace tabubotapi\Entities;

use Schneidermanuel\Dynalinker\Entity\Attribute\Entity;
use Schneidermanuel\Dynalinker\Entity\Attribute\Persist;
use Schneidermanuel\Dynalinker\Entity\Attribute\PrimaryKey;

#[Entity("player")]
class PlayerEntity
{
    #[PrimaryKey]
    #[Persist("playerId")]
    public $Id;

    #[Persist("playerName")]
    public $Name;

    #[Persist("userId")]
    public $DcId;

    #[Persist("team")]
    public $Team;

    #[Persist("gameId")]
    public $GameId;

    #[Persist("isHost")]
    public $IsHost;

    #[Persist("lastStamp")]
    public $LastSeen;
}