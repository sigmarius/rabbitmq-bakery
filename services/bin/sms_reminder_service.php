<?php

declare(strict_types=1);

use Bakery\ConnectionFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$conn = ConnectionFactory::create('notifier_app', getenv('NOTIFIER_PASS') ?: 'Notifier_Secret_1');
$ch = $conn->channel();

$ch->basic_qos(null, 10, null);

$handler = function ($msg): void {
    fwrite(STDOUT, '[sms-sim] Напоминание клиенту: ' . $msg->body . PHP_EOL);
    $msg->getChannel()->basic_ack($msg->getDeliveryTag());
};

$ch->basic_consume('q.orders.sms_sim', '', false, false, false, false, $handler);

fwrite(STDOUT, "[sms-sim] Очередь отложенных уведомлений (delayed exchange)\n");

while ($ch->is_consuming()) {
    $ch->wait();
}
