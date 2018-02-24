<?php
namespace App\simple;
/**
 * Created by PhpStorm.
 * User: LONGYONGYU186
 * Date: 2017-06-16
 * Time: 9:13
 */
require_once __DIR__.'/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Receive
{
    public function connect()
    {
        $oConneciton = new AMQPStreamConnection('dev-mq.a.pa.com', 5672, 'admin', 'admin');
        $oChannel = $oConneciton->channel();
        $oChannel->queue_declare('hello', false, false, false, false);

        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

        $cCallBack = function ($oMsg) {
            echo "[x] Received ", $oMsg->body, "\n";
        };

        $oChannel->basic_consume('hello', '', false, true, false, false, $cCallBack);

        while(count($oChannel->callbacks)) {
            $oChannel->wait();
        }

        $oChannel->close();
        $oConneciton->close();
    }
}

(new Receive)->connect();