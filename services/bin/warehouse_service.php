<?php

declare(strict_types=1);

use Bakery\ConnectionFactory;
use PhpAmqpLib\Message\AMQPMessage;

require dirname(__DIR__) . '/vendor/autoload.php';

$conn = ConnectionFactory::create('warehouse_app', getenv('WH_PASS') ?: 'Wh_Secret_1');
$ch = $conn->channel();

$ch->basic_qos(null, 1, null);

$handler = function ($msg) use ($ch): void {
    $data = json_decode($msg->body, true, 512, JSON_THROW_ON_ERROR);
    $orderId = $data['order_id'] ?? 'unknown';
    fwrite(STDOUT, "[warehouse] Сборка заказа {$orderId}\n");

    $packed = [
        'order_id' => $orderId,
        'picker_id' => 'PICK-' . bin2hex(random_bytes(2)),
        'packed_at' => date(DATE_ATOM),
        'items' => $data['items'] ?? [],
    ];

    $ch->basic_publish(
        new AMQPMessage(
            json_encode($packed, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ['content_type' => 'application/json', 'delivery_mode' => 2]
        ),
        'ex.packing',
        'packing.ready'
    );

    $ch->basic_ack($msg->getDeliveryTag());
};

$ch->basic_consume('q.orders.picking', '', false, false, false, false, $handler);

fwrite(STDOUT, "[warehouse] Ожидаю заказы (q.orders.picking, quorum)… Ctrl+C для выхода\n");

while ($ch->is_consuming()) {
    $ch->wait();
}
