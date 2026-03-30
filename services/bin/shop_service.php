<?php

declare(strict_types=1);

use Bakery\ConnectionFactory;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

require dirname(__DIR__) . '/vendor/autoload.php';

$conn = ConnectionFactory::create('shop_app', getenv('SHOP_PASS') ?: 'Shop_Secret_1');
$ch = $conn->channel();

$orderId = getenv('DEMO_ORDER_ID') ?: 'ORD-' . bin2hex(random_bytes(4));
$payload = [
    'order_id' => $orderId,
    'customer_phone' => '+79001234567',
    'items' => [
        ['sku' => 'bread-sourdough-500', 'qty' => 2],
        ['sku' => 'cookie-oat-200', 'qty' => 1],
    ],
    'address' => 'Москва, ул. Пекарей, 1',
    'created_at' => date(DATE_ATOM),
];

$body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

$ch->basic_publish(
    new AMQPMessage($body, ['content_type' => 'application/json', 'delivery_mode' => 2]),
    'ex.orders',
    'order.new'
);

$delayMs = (int) (getenv('SMS_DELAY_MS') ?: '5000');
$headers = new AMQPTable(['x-delay' => $delayMs]);
$ch->basic_publish(
    new AMQPMessage(
        json_encode(['order_id' => $orderId, 'channel' => 'sms_sim'], JSON_THROW_ON_ERROR),
        [
            'content_type' => 'application/json',
            'delivery_mode' => 2,
            'application_headers' => $headers,
        ]
    ),
    'ex.orders.delayed',
    'order.reminder'
);

fwrite(STDOUT, "[shop] Оплачен заказ {$orderId}, сообщения отправлены (AMQP + отложенное напоминание {$delayMs} ms)\n");

$ch->close();
$conn->close();
