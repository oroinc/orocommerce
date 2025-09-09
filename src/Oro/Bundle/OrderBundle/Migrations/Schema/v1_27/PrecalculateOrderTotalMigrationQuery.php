<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_27;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\OrderBundle\Async\Topic\PrecalculateOrderTotalTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerInterface;

class PrecalculateOrderTotalMigrationQuery implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    public function __construct(
        private readonly MessageProducerInterface $producer
    ) {
    }

    #[\Override]
    public function getDescription(): string|array
    {
        return 'Schedule the recalculation order total for all orders and save it in serialized data.';
    }

    #[\Override]
    public function execute(LoggerInterface $logger): void
    {
        $lastOrderId = $this->connection->executeQuery('SELECT MAX(o.id) FROM oro_order AS o')->fetchOne();
        if (null !== $lastOrderId) {
            $this->producer->send(PrecalculateOrderTotalTopic::getName(), ['lastOrderId' => $lastOrderId]);
        }
    }
}
