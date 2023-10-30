<?php

namespace tabubotapi\Entities;

use Schneidermanuel\Dynalinker\Entity\Attribute\Entity;
use Schneidermanuel\Dynalinker\Entity\Attribute\Persist;
use Schneidermanuel\Dynalinker\Entity\Attribute\PrimaryKey;

#[Entity("cardSet")]
class CardSetEntity
{
    #[Persist("cardsetId")]
    #[PrimaryKey]
    public $Id;

    #[Persist("cardsetName")]
    public $Name;

    #[Persist("cardsetOwner")]
    public $OwnerId;
}