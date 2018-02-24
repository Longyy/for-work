<?php
namespace App\work_queue;
/**
 * Created by PhpStorm.
 * User: LONGYONGYU186
 * Date: 2017-06-16
 * Time: 9:13
 */
require_once __DIR__.'/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Worker
{

    public function connect()
    {
        $oConneciton = new AMQPStreamConnection('dev-mq.a.pa.com', 5672, 'admin', 'admin');
        $oChannel = $oConneciton->channel();
        $oChannel->exchange_declare('test_exchange', 'fanout', false, false, false);

        list($sQueueName, ,) = $oChannel->queue_declare("", false, false, true, false);

        $oChannel->queue_bind($sQueueName, 'test_exchange');

        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

        $cCallBack = function ($oMsg) {
            echo "[x] Received ", $oMsg->body, "\n";
        };

        $oChannel->basic_consume($sQueueName, '', false, true, false, false, $cCallBack);

        while(count($oChannel->callbacks)) {
            $oChannel->wait();
        }

        $oChannel->close();
        $oConneciton->close();
    }
}

(new Worker)->connect();