<?php

declare(strict_types=1);

use Bakery\ConnectionFactory;
use PhpAmqpLib\Message\AMQPMessage;

require dirname(__DIR__) . '/vendor/autoload.php';

$conn = ConnectionFactory::create('production_app', getenv('PROD_PASS') ?: 'Prod_Secret_1');
$ch = $conn->channel();

$job = [
    'batch_id' => getenv('BATCH_ID') ?: 'BATCH-' . date('Ymd-His'),
    'product_line' => 'cookie-line-2',
    'simulate_critical_stock' => filter_var(getenv('SIMULATE_CRITICAL') ?: 'false', FILTER_VALIDATE_BOOL),
];

$ch->basic_publish(
    new AMQPMessage(json_encode($job, JSON_THROW_ON_ERROR), [
        'content_type' => 'application/json',
        'delivery_mode' => 2,
    ]),
    'ex.production',
    'bake'
);

fwrite(STDOUT, '[schedule] Отправлено задание в ex.production / bake: ' . json_encode($job, JSON_UNESCAPED_UNICODE) . PHP_EOL);

$ch->close();
$conn->close();
