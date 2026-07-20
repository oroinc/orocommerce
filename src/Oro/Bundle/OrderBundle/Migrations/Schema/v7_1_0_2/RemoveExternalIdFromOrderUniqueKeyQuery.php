<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v7_1_0_2;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\OrderBundle\Entity\Order;
use Psr\Log\LoggerInterface;

/**
 * Removes only the "external_id" entry from the persisted "unique_key" config of the Order entity.
 */
class RemoveExternalIdFromOrderUniqueKeyQuery implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    private const string UNIQUE_KEY_NAME = 'external_id';

    #[\Override]
    public function getDescription(): string
    {
        return sprintf(
            'Remove the "%s" unique key entry from the persisted unique_key config of "%s"',
            self::UNIQUE_KEY_NAME,
            Order::class
        );
    }

    #[\Override]
    public function execute(LoggerInterface $logger): void
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, data FROM oro_entity_config WHERE class_name = ?',
            [Order::class]
        );

        foreach ($rows as $row) {
            $this->cleanRow($row);
        }
    }

    private function cleanRow(array $row): void
    {
        $data = $row['data'] ? $this->connection->convertToPHPValue($row['data'], Types::ARRAY) : [];

        $keys = $data['extend']['unique_key']['keys'] ?? null;
        if (!\is_array($keys)) {
            return;
        }

        $filteredKeys = array_values(array_filter(
            $keys,
            static fn (array $key): bool => ($key['name'] ?? null) !== self::UNIQUE_KEY_NAME
        ));

        if (\count($filteredKeys) === \count($keys)) {
            return;
        }

        $data['extend']['unique_key']['keys'] = $filteredKeys;

        $sql = 'UPDATE oro_entity_config SET data = ? WHERE id = ?';
        $parameters = [
            $this->connection->convertToDatabaseValue($data, Types::ARRAY),
            $row['id'],
        ];
        $types = [Types::STRING, Types::INTEGER];
        $this->connection->executeStatement($sql, $parameters, $types);
    }
}
