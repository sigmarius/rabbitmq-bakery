<?php

declare(strict_types=1);

use Bakery\ConnectionFactory;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

require dirname(__DIR__) . '/vendor/autoload.php';

$conn = ConnectionFactory::create('production_app', getenv('PROD_PASS') ?: 'Prod_Secret_1');
$ch = $conn->channel();

$ch->basic_qos(null, 1, null);

$handler = function ($msg) use ($ch): void {
    $job = json_decode($msg->body, true, 512, JSON_THROW_ON_ERROR);
    $batch = $job['batch_id'] ?? 'batch-unknown';
    fwrite(STDOUT, "[production] Выпекаем партию {$batch}\n");

    $consumption = [
        'type' => 'raw_material_consumption',
        'batch_id' => $batch,
        'flour_kg' => 120.5,
        'sugar_kg' => 15.0,
        'butter_kg' => 8.0,
        'reported_at' => date(DATE_ATOM),
    ];

    $ch->basic_publish(
        new AMQPMessage(
            json_encode($consumption, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ['content_type' => 'application/json', 'delivery_mode' => 2]
        ),
        'ex.inventory',
        ''
    );

    if (!empty($job['simulate_critical_stock'])) {
        $alert = new AMQPMessage(
            json_encode(['sku' => 'flour-premium', 'remaining_kg' => 50], JSON_THROW_ON_ERROR),
            [
                'content_type' => 'application/json',
                'delivery_mode' => 2,
                'application_headers' => new AMQPTable([
                    'level' => 'critical',
                    'subsystem' => 'raw_material',
                ]),
            ]
        );
        $ch->basic_publish($alert, 'ex.alerts', '');
    }

    $ch->basic_ack($msg->getDeliveryTag());
};

$ch->basic_consume('q.production.bake', '', false, false, false, false, $handler);

fwrite(STDOUT, "[production] Жду задания выпечки (q.production.bake)…\n");

while ($ch->is_consuming()) {
    $ch->wait();
}
