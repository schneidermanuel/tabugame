<?php

namespace tabubotapi\Entities;

use Schneidermanuel\Dynalinker\Entity\Attribute\Entity;
use Schneidermanuel\Dynalinker\Entity\Attribute\Persist;
use Schneidermanuel\Dynalinker\Entity\Attribute\PrimaryKey;

#[Entity("card")]
class CardEntity
{
    #[Persist("cardId")]
    #[PrimaryKey]
    public $Id;
    #[Persist("contributerName")]
    public $ContributerName;
    #[Persist("text")]
    public $Text;
    #[Persist("keyword1")]
    public $Keyword1;
    #[Persist("keyword2")]
    public $Keyword2;
    #[Persist("keyword3")]
    public $Keyword3;
    #[Persist("keyword4")]
    public $Keyword4;
    #[Persist("cardSetId")]
    public $CardSetId;
}