<?php

namespace OroB2B\Bundle\MenuBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BMenuBundle implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createConstraint(
            $schema,
            $queries,
            'orob2b_menu_item_title',
            'oro_fallback_localization_val',
            ['localized_value_id']
        );
        $this->removeMenuItemSerializedDataColumn($schema);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $tableName
     * @param string $foreignTable
     * @param array $fields
     */
    protected function createConstraint(Schema $schema, QueryBag $queries, $tableName, $foreignTable, array $fields)
    {
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            $tableName,
            $foreignTable,
            $fields,
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function removeMenuItemSerializedDataColumn(Schema $schema)
    {
        $table = $schema->getTable('orob2b_menu_item');
        if ($table->hasColumn('serialized_data') &&
            !class_exists('Oro\Bundle\EntitySerializedFieldsBundle\OroEntitySerializedFieldsBundle')
        ) {
            $table->dropColumn('serialized_data');
        }
    }
}
