<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_25_2;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\OrderBundle\Async\Topic\PrecalculateOrderTotalTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerInterface;

class PrecalculateOrderTotalMigrationQuery implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    private MessageProducerInterface $producer;

    public function __construct(MessageProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    #[\Override]
    public function getDescription()
    {
        return 'Schedule the recalculation order total for all orders and save it in serialized data.';
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $lastOrderId = $this->connection->executeQuery('SELECT MAX(o.id) FROM oro_order AS o')->fetchOne();
        if (null !== $lastOrderId) {
            $this->producer->send(PrecalculateOrderTotalTopic::getName(), ['lastOrderId' => $lastOrderId]);
        }
    }
}
