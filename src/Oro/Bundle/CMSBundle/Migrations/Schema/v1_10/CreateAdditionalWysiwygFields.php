<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds additional WYSIWYG fields (_style and _properties) if they did not exist during the platform update.
 */
class CreateAdditionalWysiwygFields implements Migration, ConnectionAwareInterface, ExtendExtensionAwareInterface
{
    private Connection $connection;
    private ExtendExtension $extendExtension;

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    public function setExtendExtension(ExtendExtension $extendExtension): void
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * @inheridoc
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $fields = $this->fetchWYSIWYGFields();
        foreach ($fields as $field) {
            $className = $field['class_name'];
            $fieldName = $field['field_name'];
            $fieldOptions = $this->decodeData($field['data']);

            /** @var EntityMetadata $metadata */
            $classTableName = $this->extendExtension->getTableNameByEntityClass($className);
            $classTable = $schema->getTable($classTableName);

            $this->configureColumns($classTable, $fieldName, $fieldOptions);
        }
    }

    /**
     * Adds additional WYSIWYG column if additional field not exists.
     * Adds additional WYSIWYG column extend options if options not exists.
     *
     * @param Table $table
     * @param string $column
     * @param array $options - extend field options.
     */
    private function configureColumns(Table $table, string $column, array $options): void
    {
        $defaultOptions = [
            'notnull' => false,
            OroOptions::KEY => [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                'extend' => $options['extend'],
            ]
        ];

        $this->configureColumn(
            $table,
            $column,
            $defaultOptions,
            WYSIWYGStyleType::TYPE_SUFFIX,
            WYSIWYGStyleType::TYPE
        );

        $this->configureColumn(
            $table,
            $column,
            $defaultOptions,
            WYSIWYGPropertiesType::TYPE_SUFFIX,
            WYSIWYGPropertiesType::TYPE
        );
    }

    private function configureColumn(Table $table, string $column, array $options, string $suffix, string $type): void
    {
        $columnName = $column . $suffix;
        if (!$table->hasColumn($columnName)) {
            $table->addColumn($columnName, $type, $options);
        }
    }

    /**
     * @return array [['field_name' => 'field', 'data' => 'base64_encode('data'), 'class_name' => 'FQCN class name']]
     */
    private function fetchWYSIWYGFields(): array
    {
        $sql = 'SELECT efc.field_name, efc.data, ec.class_name
                FROM oro_entity_config_field efc
                LEFT JOIN oro_entity_config ec ON efc.entity_id = ec.id 
                WHERE type = \'wysiwyg\'';

        return $this->connection->fetchAllAssociative($sql);
    }

    /**
     * Decodes serialized option data.
     */
    private function decodeData(string $data): array
    {
        return $this->connection->convertToPHPValue($data, Types::ARRAY);
    }
}
