<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_15_1;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Psr\Log\LoggerInterface;

class UpdateQuoteGuestAccessIdQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Generates the guestAccessId for Quotes.');

        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * {@inheritdoc}
     */
    private function doExecute(LoggerInterface $logger, $dryRun = false): void
    {
        $query = 'UPDATE oro_sale_quote SET guest_access_id = :guest_access_id WHERE id = :id';
        $types = ['guest_access_id' => Type::STRING, 'id' => Type::INTEGER];

        foreach ($this->getQuoteIds($logger) as $id) {
            $params = ['id' => $id, 'guest_access_id' => UUIDGenerator::v4()];

            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeUpdate($query, $params, $types);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return array
     */
    private function getQuoteIds(LoggerInterface $logger): array
    {
        $query = 'SELECT id FROM oro_sale_quote WHERE guest_access_id IS NULL';

        $this->logQuery($logger, $query);

        return array_column($this->connection->fetchAll($query), 'id');
    }
}
