<?php

require_once "Mail/Queue.php";

//set some mailing options
$db_options = array(
    'type'        => 'db',
    'dsn'         => 'mysql://scholviu_ors:rilinc@localhost/scholviu_research',
    'mail_table'  => 'mail_queue',
);
$mail_options = array(
    'driver'   => 'smtp',
    'host'     => 'localhost',
    'port'     => 25,
    'auth'     => false,
    'username' => '',
    'password' => '',
);
?>
