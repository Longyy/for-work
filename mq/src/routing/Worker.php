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

    public function connect($argv)
    {
        $oConneciton = new AMQPStreamConnection('dev-mq.a.pa.com', 5672, 'admin', 'admin', '/arm');
        $oChannel = $oConneciton->channel();
        $oChannel->exchange_declare('exchange_push', 'fanout', false, true, false);


        // 仅仅对message进行确认不能保证message不丢失，比如RabbitMQ崩溃了queue就会丢失
        // 因此还需使用durable=True声明queue是持久化的，这样即便Rabb崩溃了重启后queue仍然存在
        list($sQueueName, ,) = $oChannel->queue_declare("queue_push", false, true, false, false);

        $severities = array_slice($argv, 1);
        if(empty($severities )) {
            file_put_contents('php://stderr', "Usage: $argv[0] [push] [info] [warning] [error]\n");
            exit(1);
        }

        foreach($severities as $sItem) {
            $oChannel->queue_bind($sQueueName, 'exchange_push', $sItem);
        }

        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
        $i=0;
        $cCallBack = function ($oMsg) use (&$i) {
            sleep(5);
            echo ' [x] ',$oMsg->delivery_info['routing_key'], ':', $oMsg->body, " $i", "\n";
            $i++;

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

        /**
         * 这里设置了no_ack=false这个参数，也即需要对message进行确认（默认行为）
         * 否则consumer在偶然down后其正在处理和分配到该consumer还未处理的message可能发生丢失
         * 因为此时RabbitMQ在发送完message后立即从内存删除该message
         * RabbitMQ中没有超时的概念，只有在consumer down掉后重新分发message
         * 假如没有设置no_ack=True则consumer在偶然down掉后其正在处理和分配至该consumer但还未来得及处理的message会重新分配到其他consumer
         */
        $oChannel->basic_consume($sQueueName, '', false, false, false, false, $cCallBack);

        while(count($oChannel->callbacks)) {
            $oChannel->wait();
        }

        $oChannel->close();
        $oConneciton->close();
    }
}

(new Worker)->connect($argv);