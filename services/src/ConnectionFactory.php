<?php

declare(strict_types=1);

namespace Bakery;

use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;

final class ConnectionFactory
{
    public static function create(string $user, string $password): AMQPStreamConnection|AMQPSSLConnection
    {
        $host = getenv('RABBITMQ_HOST') ?: '127.0.0.1';
        $vhost = getenv('RABBITMQ_VHOST') ?: 'bakery';
        $useTls = filter_var(getenv('RABBITMQ_TLS') ?: 'false', FILTER_VALIDATE_BOOL);

        $base = dirname(__DIR__) . '/../docker/certs/ca.pem';
        $cafile = getenv('RABBITMQ_CAFILE') ?: $base;

        if ($useTls) {
            $port = (int) (getenv('RABBITMQ_TLS_PORT') ?: 5671);
            $ssl = [
                'cafile' => $cafile,
                'verify_peer' => true,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ];

            return new AMQPSSLConnection($host, $port, $user, $password, $vhost, $ssl);
        }

        $port = (int) (getenv('RABBITMQ_PORT') ?: 5672);

        return new AMQPStreamConnection($host, $port, $user, $password, $vhost);
    }
}
