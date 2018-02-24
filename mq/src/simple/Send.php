<?php
namespace App\simple;
/**
 * Created by PhpStorm.
 * User: LONGYONGYU186
 * Date: 2017-06-16
 * Time: 9:13
 */
require_once dirname(dirname(dirname(__FILE__))).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Send
{
    public function connect()
    {
        $oConneciton = new AMQPStreamConnection('dev-mq.a.pa.com', 5672, 'admin', 'admin');
        $oChannel = $oConneciton->channel();
        $oChannel->queue_declare('hello', false, false, false, false);

        $oMsg = new AMQPMessage('Hello world');
        $oChannel->basic_publish($oMsg, '', 'hello');

        echo "[x] Sent 'Hello world' \n";

        $oChannel->close();
        $oConneciton->close();

    }
}

(new Send)->connect();