<?php

namespace tabubotapi\Controllers;

use Schneidermanuel\Dynalinker\Controller\HttpGet;

class AuthenticationController
{
    #[HttpGet("callback")]
    public function CodeCallback()
    {
        echo "hello world";
    }
}