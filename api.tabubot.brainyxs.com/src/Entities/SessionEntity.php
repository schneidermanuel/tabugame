<?php

namespace tabubotapi\Entities;

use Schneidermanuel\Dynalinker\Entity\Attribute\Entity;
use Schneidermanuel\Dynalinker\Entity\Attribute\Persist;
use Schneidermanuel\Dynalinker\Entity\Attribute\PrimaryKey;

#[Entity("playerSession")]
class SessionEntity
{
    #[Persist("otpId")]
    #[PrimaryKey]
    public $Id;

    #[Persist("playerId")]
    public $PlayerId;

    #[Persist("Identifier")]
    public $Identifier;

    #[Persist("generated")]
    public $GeneratedTime;
}