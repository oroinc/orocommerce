<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_17_7;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\OrderBundle\Async\Topic\PrecalculateOrderTotalTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerInterface;

class PrecalculateOrderTotalMigrationQuery implements MigrationQuery, ConnectionAwareInterface
{
    /** @var MessageProducerInterface */
    private $producer;

    /** @var Connection */
    private $connection;

    public function __construct(MessageProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    #[\Override]
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
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
