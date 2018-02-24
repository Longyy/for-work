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

        $oConneciton = new AMQPStreamConnection('dev-mq.a.pa.com', 5672, 'admin', 'admin', '/arm');
        $oChannel = $oConneciton->channel();
        $oChannel->exchange_declare('exchange_push', 'fanout', false, true, false);

        $sSeverity = isset($argv[1]) && !empty($argv[1]) ? $argv[1] : 'info';

        $sData = implode(' ', array_slice($argv, 2));

        if(empty($sData)) {
            $sData = 'Hello world';
        }

        $aData = [
            'iUserID' => 1,
            'trace_id' => $sSeverity,
            'sCode' => 'CREDIT_SPECIAL_BIRTHDAY',
            'aParam' => [
                'iCredit' => 1,
                'sUserName' => '张三',
            ]
        ];
        $sData = json_encode($aData);




        /**
         * 除了要声明queue是持久化的外，还需声明message是持久化的,
         * delivery_mode=2指明message为持久的
         * 这样一来RabbitMQ崩溃重启后queue仍然存在其中的message也仍然存在
         * 需注意的是将message标记为持久的并不能完全保证message不丢失，因为
         * 从RabbitMQ接收到message到将其存储到disk仍需一段时间，若此时RabbitMQ崩溃则message会丢失
         * 况且RabbitMQ不会对每条message做fsync动作
         * 可通过publisher confirms实现更强壮的持久性保证
         */
        $oMsg = new AMQPMessage($sData, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

        $oChannel->basic_publish($oMsg, 'exchange_push', $sSeverity);

        echo "[x] Sent ", $sSeverity, ':', $sData, "\n";


        $oChannel->close();
        $oConneciton->close();

    }
}

(new Task)->connect($argv);
?>