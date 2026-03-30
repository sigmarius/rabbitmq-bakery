#!/usr/bin/env bash
set -euo pipefail

export MSYS_NO_PATHCONV=1

CONTAINER="${1:-rabbitmq-bakery-final}"

docker exec "${CONTAINER}" rabbitmqctl await_startup

docker exec "${CONTAINER}" rabbitmqctl set_parameter -p bakery shovel completed_orders_to_archive \
'{"src-uri":"amqp://ops_shovel:Shovel_Secret_1@127.0.0.1:5672/bakery","src-queue":"q.orders.completed","dest-uri":"amqp://ops_shovel:Shovel_Secret_1@127.0.0.1:5672/bakery_archive","dest-queue":"q.archived.orders","ack-mode":"on-confirm","delete-after":"never"}'

docker exec "${CONTAINER}" rabbitmqctl set_parameter -p bakery_archive federation-upstream upstream-bakery \
'{"uri":"amqp://federation_link:Fed_Secret_1@127.0.0.1:5672/bakery","ack-mode":"on-confirm"}'

docker exec "${CONTAINER}" rabbitmqctl set_policy -p bakery_archive federate-inventory-downstream \
  "^ex\\.inventory$" '{"federation-upstream":"upstream-bakery"}' --apply-to exchanges

echo "Shovel и federation-upstream/policy применены."
