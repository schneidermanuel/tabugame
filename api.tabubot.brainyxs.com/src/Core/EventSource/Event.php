<?php

namespace tabubotapi\Core\EventSource;

class Event
{
    public static function StartSource()
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        self::SendData("Event Source started");
    }

    public static function SendData(string $data, $type = null)
    {
        $res = new \stdClass();
        $res->Type = $type;
        $res->Content = $data;
        echo "event: message\n";
        echo "data:" . json_encode($res) . "\n\n";
        echo PHP_EOL;
        @ob_flush();
        flush();
    }
}