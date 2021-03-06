<?php
namespace App\work_queue;
/**
 * Created by PhpStorm.
 * User: LONGYONGYU186
 * Date: 2017-06-16
 * Time: 9:13
 */
require_once dirname(dirname(dirname(__FILE__))).'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Task
{
    public function connect($argv)
    {

        $oConneciton = new AMQPStreamConnection('dev-mq.a.pa.com', 5672, 'admin', 'admin');
        $oChannel = $oConneciton->channel();
        $oChannel->queue_declare('work_queue_with_durability', false, true, false, false);

        $sData = implode(' ', array_slice($argv, 1));

        if(empty($sData)) {
            $sData = 'Hello world';
        }

        $oMsg = new AMQPMessage($sData, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

        $oChannel->basic_publish($oMsg, '', 'work_queue');

        echo "[x] Sent ", $sData, "\n";


        $oChannel->close();
        $oConneciton->close();

    }
}

(new Task)->connect($argv);
?>