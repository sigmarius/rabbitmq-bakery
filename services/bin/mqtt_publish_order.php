<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

$host = getenv('RABBITMQ_HOST') ?: '127.0.0.1';
$port = (int) (getenv('MQTT_PORT') ?: 1883);
$user = getenv('MQTT_USER') ?: 'mqtt_stomp';
$pass = getenv('MQTT_PASS') ?: 'Mqtt_Secret_1';

$orderId = getenv('DEMO_ORDER_ID') ?: 'MQTT-ORD-' . bin2hex(random_bytes(3));
$payload = json_encode([
    'order_id' => $orderId,
    'customer_phone' => '+79007654321',
    'items' => [['sku' => 'bread-rye-400', 'qty' => 1]],
    'address' => 'Санкт-Петербург, Мукомольный пр., 5',
    'created_at' => date(DATE_ATOM),
], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

$client = new MqttClient($host, $port, 'bakery-mqtt-demo-' . bin2hex(random_bytes(2)));
$settings = (new ConnectionSettings())
    ->setUsername($user)
    ->setPassword($pass);

$client->connect($settings, true);
$topic = 'bakery/orders/new';
$client->publish($topic, $payload, 1);
$client->disconnect();

fwrite(STDOUT, "[mqtt] Опубликовано в {$topic}, маршрут amq.topic → bakery.orders.new → q.orders.picking\n");
