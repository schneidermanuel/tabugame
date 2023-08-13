<?php

class mysql
{

    private $mysql = "";
    private $text = "";

    public function __construct()
    {
        global $config;
        $mysql_auth = array();
        $this->mysql = mysqli_init();

        $this->mysql->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
        $this->mysql->options(MYSQLI_OPT_READ_TIMEOUT, 30);

        $mysql_auth['ip'] = $_ENV["DATABASE_IP"];
        $mysql_auth['username'] = $_ENV["DATABASE_USER"];
        $mysql_auth['password'] = $_ENV["DATABASE_PASSWORD"];
        $mysql_auth['db'] = $_ENV["DATABASE_DB"];

        try {
            $this->mysql->real_connect($mysql_auth['ip'], $mysql_auth['username'], $mysql_auth['password'], $mysql_auth['db']);
        } catch (Exception $e) {
            if (stringContains($e->getMessage(), "Unknown database")) {
                throw new SetupException("Given Database " . $config['mysql']['database'] . " doesn't exist");
            } else {
                throw new MySQLException($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
        }

        $this->mysql->set_charset("utf8");
    }

    public function query(...$query)
    {
        $build = "";
        foreach ($query as $key => $value) {
            if ($key == "0") {
                $build = $value;
            } else {
                $to = mysqli_real_escape_string($this->mysql, $value);

                $build = $this->left_replace($build, '%s', $to);
            }
        }
        $this->text = $build;
        return $this->mysql->query($build);
    }

    public function getText()
    {
        return $this->text;
    }

    function left_replace($text, $find, $replace)
    {
        return implode($replace, explode($find, $text, 2));
    }
}

$mysqli = new mysql();
