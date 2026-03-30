<?php

declare(strict_types=1);

use Bakery\ConnectionFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$conn = ConnectionFactory::create('procurement_app', getenv('PROC_PASS') ?: 'Proc_Secret_1');
$ch = $conn->channel();

$ch->basic_qos(null, 10, null);

$handler = function ($msg): void {
    fwrite(STDOUT, '[procurement] Сигнал: ' . $msg->body . PHP_EOL);
    $msg->getChannel()->basic_ack($msg->getDeliveryTag());
};

$ch->basic_consume('q.inventory.signals', '', false, false, false, false, $handler);

fwrite(STDOUT, "[procurement] Слушаем fanout ex.inventory → q.inventory.signals\n");

while ($ch->is_consuming()) {
    $ch->wait();
}
