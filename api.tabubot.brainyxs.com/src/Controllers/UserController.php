<?php

namespace tabubotapi\Controllers;

use Schneidermanuel\Dynalinker\Controller\HttpGet;
use tabubotapi\Core\Authenticator;

class UserController
{
    #[HttpGet("me")]
    public function Me()
    {
        $user = Authenticator::GetUser();
        if (!isset($user))
        {
            echo json_encode(array("authenticated" => false));
            die();
        }
        echo json_encode(array("authenticated" => true, "user" => $user));
    }
}