<?php

declare(strict_types=1);

use Bakery\ConnectionFactory;
use PhpAmqpLib\Message\AMQPMessage;

require dirname(__DIR__) . '/vendor/autoload.php';

$conn = ConnectionFactory::create('courier_app', getenv('COUR_PASS') ?: 'Cour_Secret_1');
$ch = $conn->channel();

$ch->basic_qos(null, 1, null);

$handler = function ($msg) use ($ch): void {
    $data = json_decode($msg->body, true, 512, JSON_THROW_ON_ERROR);
    $orderId = $data['order_id'] ?? 'unknown';
    fwrite(STDOUT, "[courier] Доставляю {$orderId}\n");
    usleep(300_000);

    $done = [
        'order_id' => $orderId,
        'courier_id' => 'CAR-' . bin2hex(random_bytes(2)),
        'delivered_at' => date(DATE_ATOM),
    ];

    $ch->basic_publish(
        new AMQPMessage(json_encode($done, JSON_THROW_ON_ERROR), [
            'content_type' => 'application/json',
            'delivery_mode' => 2,
        ]),
        'ex.orders',
        'order.delivered'
    );

    $ch->basic_ack($msg->getDeliveryTag());
};

$ch->basic_consume('q.delivery.pending', '', false, false, false, false, $handler);

fwrite(STDOUT, "[courier] На линии (q.delivery.pending)…\n");

while ($ch->is_consuming()) {
    $ch->wait();
}
