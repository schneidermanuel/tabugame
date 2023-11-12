<?php

namespace tabubotapi\Core;

class Response
{
    public static function Send($content, $type = "OK")
    {
        $data = new \stdClass();
        $data->Status = $type;
        $data->Message = $content;
        echo json_encode($data);
        die();
    }
}