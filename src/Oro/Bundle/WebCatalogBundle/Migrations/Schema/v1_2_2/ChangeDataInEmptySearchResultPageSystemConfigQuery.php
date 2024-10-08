<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Migrations\Schema\v1_2_2;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Psr\Log\LoggerInterface;

/**
 * Extracts serialized content node from "array_value" column of each "oro_web_catalog.empty_search_result_page"
 * system config setting row and updates it to the content node id in "text_value" column.
 * Clears the "array_value" afterward.
 */
class ChangeDataInEmptySearchResultPageSystemConfigQuery extends ParametrizedMigrationQuery
{
    public function getDescription(): array
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    public function execute(LoggerInterface $logger): void
    {
        $this->doExecute($logger, false);
    }

    private function doExecute(LoggerInterface $logger, bool $dryRun): void
    {
        $query = 'SELECT id, array_value FROM oro_config_value WHERE section = :section AND name = :name';
        $parameters = [
            'section' => 'oro_web_catalog',
            'name' => 'empty_search_result_page',
        ];
        $types = [
            'section' => Types::STRING,
            'name' => Types::STRING,
        ];

        $this->logQuery($logger, $query, $parameters, $types);

        /** @var array<array{id: int, array_value: string}> $configValueRows */
        $configValueRows = $this->connection->fetchAllAssociative($query, $parameters, $types);
        foreach ($configValueRows as $configValueRow) {
            $value = base64_decode($configValueRow['array_value']);
            $value = unserialize($value);

            if (is_array($value) && $value['contentNode'] instanceof ContentNode) {
                $newValue = $value['contentNode']->getId();

                $query = 'UPDATE oro_config_value SET 
                            type = :type, 
                            text_value = :value, 
                            array_value = :null_value 
                        WHERE id = :id';
                $parameters = [
                    'type' => ConfigValue::FIELD_SCALAR_TYPE,
                    'value' => (string)$newValue,
                    'id' => $configValueRow['id'],
                    'null_value' => base64_encode(serialize(null)),
                ];
                $types = [
                    'type' => Types::STRING,
                    'value' => Types::STRING,
                    'id' => Types::STRING,
                    'null_value' => Types::STRING,
                ];

                $this->logQuery($logger, $query, $parameters, $types);
                if (!$dryRun) {
                    $this->connection->executeStatement($query, $parameters, $types);
                }
            }
        }
    }
}
