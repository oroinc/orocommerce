<?php

namespace Oro\Bundle\CMSBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Creates missing WYSIWYG fields (_style and _properties) if they did not exist before.
 */
class RepairAllWysiwygFieldsSchemaMigration implements
    Migration,
    ConnectionAwareInterface,
    ExtendExtensionAwareInterface
{
    use ConnectionAwareTrait;
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $fields = $this->getAllWysiwygFields();
        foreach ($fields as $field) {
            $className = $field['class_name'];
            $fieldName = $field['field_name'];
            $fieldOptions = $this->decodeData($field['data']);

            $this->repairField($schema, $queries, $className, $fieldName, $fieldOptions);
        }
    }

    protected function repairField(
        Schema $schema,
        QueryBag $queries,
        string $className,
        string $fieldName,
        array $fieldOptions
    ): void {
        $migration = new RepairWysiwygFieldSchemaMigration(
            $this->extendExtension,
            $className,
            $fieldName,
            $fieldOptions
        );

        $migration->up($schema, $queries);
    }

    /**
     * @return array<array<string,string|array>>
     *     [['field_name' => 'field name', 'data' => 'base64 encoded options', 'class_name' => 'FQCN class name']]
     */
    protected function getAllWysiwygFields(): array
    {
        $sql = 'SELECT efc.field_name, efc.data, ec.class_name
                FROM oro_entity_config_field efc
                LEFT JOIN oro_entity_config ec ON efc.entity_id = ec.id 
                WHERE type = :field_type';

        return $this->connection->fetchAllAssociative(
            $sql,
            ['field_type' => WYSIWYGType::TYPE],
            ['field_type' => Types::STRING]
        );
    }

    /**
     * Decodes serialized options data.
     */
    protected function decodeData(string $data): array
    {
        return $this->connection->convertToPHPValue($data, Types::ARRAY);
    }
}
