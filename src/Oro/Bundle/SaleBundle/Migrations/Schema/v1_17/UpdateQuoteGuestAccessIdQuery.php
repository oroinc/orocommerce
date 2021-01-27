<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Psr\Log\LoggerInterface;

/**
 * Generates guest access id for existing Quote entities.
 */
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
        $types = ['guest_access_id' => Types::STRING, 'id' => Types::INTEGER];

        $quoteIds = $this->getQuoteIds($logger, $dryRun);
        foreach ($quoteIds as $id) {
            $params = ['id' => $id, 'guest_access_id' => UUIDGenerator::v4()];

            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeStatement($query, $params, $types);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     *
     * @return array
     */
    private function getQuoteIds(LoggerInterface $logger, $dryRun = false): array
    {
        $query = 'SELECT id FROM oro_sale_quote WHERE guest_access_id IS NULL';

        $this->logQuery($logger, $query);

        return $dryRun ? [] : array_column($this->connection->fetchAll($query), 'id');
    }
}
