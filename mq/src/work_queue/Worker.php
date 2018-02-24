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
        $oChannel->queue_declare('work_queue_with_durability', false, true, false, false);

        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

        $cCallBack = function ($oMsg) {
            echo "[x] Received ", $oMsg->body, "\n";
            sleep(substr_count($oMsg->body, '.'));
            echo "[x] Done", "\n";
            // 发送确认
            $oMsg->delivery_info['channel']->basic_ack($oMsg->delivery_info['delivery_tag']);
        };

        /**
         * 若存在多个consumer每个consumer的负载可能不同，有些处理的快有些处理的慢
         * RabbitMQ并不管这些，只是简单的以round-robin的方式分配message
         * 这可能造成某些consumer积压很多任务处理不完而一些consumer长期处于饥饿状态
         * 可以使用prefetch_count=1的basic_qos方法可告知RabbitMQ只有在consumer处理并确认了上一个message后才分配新的message给他
         * 否则分给另一个空闲的consumer
         */
        $oChannel->basic_qos(null, 1, null);

        $oChannel->basic_consume('work_queue', '', false, false, false, false, $cCallBack);

        while(count($oChannel->callbacks)) {
            $oChannel->wait();
        }

        $oChannel->close();
        $oConneciton->close();
    }
}

(new Worker)->connect();