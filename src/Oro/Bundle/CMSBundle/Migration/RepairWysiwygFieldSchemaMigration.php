<?php

namespace Oro\Bundle\CMSBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Creates missing *_style and *_properties columns for the specified WYSIWYG field.
 */
class RepairWysiwygFieldSchemaMigration implements Migration
{
    protected ExtendExtension $extendExtension;

    protected string $className;

    protected string $fieldName;

    protected array $fieldOptions;

    public function __construct(
        ExtendExtension $extendExtension,
        string $className,
        string $fieldName,
        array $fieldOptions = []
    ) {
        $this->extendExtension = $extendExtension;
        $this->className = $className;
        $this->fieldName = $fieldName;
        $this->fieldOptions = $fieldOptions;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $classTableName = $this->extendExtension->getTableNameByEntityClass($this->className);
        if ($schema->hasTable($classTableName)) {
            $classTable = $schema->getTable($classTableName);

            $this->configureColumns($classTable, $this->fieldName, $this->fieldOptions);
        }
    }

    /**
     * Adds additional WYSIWYG column if additional field not exists.
     * Adds additional WYSIWYG column extend options if options not exists.
     *
     * @param Table $table
     * @param string $column
     * @param array $options Extended field options
     */
    protected function configureColumns(Table $table, string $column, array $options): void
    {
        $defaultOptions = [
            'notnull' => false,
            OroOptions::KEY => [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                'extend' => $options['extend'] ?? true,
            ],
        ];

        $this->addWysiwygColumn(
            $table,
            $column,
            $defaultOptions,
            WYSIWYGStyleType::TYPE_SUFFIX,
            WYSIWYGStyleType::TYPE
        );

        $this->addWysiwygColumn(
            $table,
            $column,
            $defaultOptions,
            WYSIWYGPropertiesType::TYPE_SUFFIX,
            WYSIWYGPropertiesType::TYPE
        );
    }

    protected function addWysiwygColumn(
        Table $table,
        string $column,
        array $options,
        string $suffix,
        string $type
    ): void {
        $columnName = $column . $suffix;
        if (!$table->hasColumn($columnName)) {
            $table->addColumn($columnName, $type, $options);
        }
    }
}
