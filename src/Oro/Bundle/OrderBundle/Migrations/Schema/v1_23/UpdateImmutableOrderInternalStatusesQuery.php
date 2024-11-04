<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_23;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Migrations\Data\ORM\LoadAdditionalOrderInternalStatuses;
use Psr\Log\LoggerInterface;

class UpdateImmutableOrderInternalStatusesQuery extends ParametrizedMigrationQuery
{
    #[\Override]
    public function getDescription(): array|string
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger): void
    {
        $this->doExecute($logger);
    }

    private function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $sql = 'SELECT id, data FROM oro_entity_config_field'
            . ' WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :className LIMIT 1)'
            . ' AND field_name = :fieldName LIMIT 1';
        $params = ['className' => Order::class, 'fieldName' => 'internal_status'];
        $types = ['className' => Types::STRING, 'fieldName' => Types::STRING];
        $this->logQuery($logger, $sql, $params, $types);

        $row = $this->connection->fetchAssociative($sql, $params, $types);
        if (false === $row || !isset($row['data'])) {
            return;
        }

        $data = $this->connection->convertToPHPValue($row['data'], Types::ARRAY);
        $data['enum']['immutable_codes'] = array_merge(
            $data['enum']['immutable_codes'] ?? [],
            ExtendHelper::mapToEnumOptionIds(
                Order::INTERNAL_STATUS_CODE,
                LoadAdditionalOrderInternalStatuses::getDataKeys()
            )
        );

        $sql = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';
        $params = ['data' => $data, 'id' => $row['id']];
        $types = ['data' => Types::ARRAY, 'id' => Types::INTEGER];
        $this->logQuery($logger, $sql, $params, $types);
        if (!$dryRun) {
            $this->connection->executeStatement($sql, $params, $types);
        }
    }
}
