<?php

declare(strict_types=1);

use PhpAmqpLib\Connection\AMQPStreamConnection;

require dirname(__DIR__) . '/vendor/autoload.php';

$host = getenv('RABBITMQ_HOST') ?: '127.0.0.1';
$port = (int) (getenv('RABBITMQ_PORT') ?: 5672);

$conn = new AMQPStreamConnection(
    $host,
    $port,
    'federation_link',
    getenv('FED_PASS') ?: 'Fed_Secret_1',
    'bakery_archive'
);
$ch = $conn->channel();

$ch->basic_qos(null, 20, null);

$handler = function ($msg): void {
    fwrite(STDOUT, '[archive] Реплика сигнала снабжения: ' . $msg->body . PHP_EOL);
    $msg->getChannel()->basic_ack($msg->getDeliveryTag());
};

$ch->basic_consume('q.inventory.replica', '', false, false, false, false, $handler);

fwrite(STDOUT, "[archive] bakery_archive / q.inventory.replica (federation)\n");

while ($ch->is_consuming()) {
    $ch->wait();
}
