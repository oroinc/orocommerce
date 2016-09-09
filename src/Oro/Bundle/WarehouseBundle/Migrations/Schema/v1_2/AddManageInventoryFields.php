<?php

namespace Oro\Bundle\WarehouseBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddManageInventoryFields implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * @param ExtendExtension $extendExtension
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addManageInventoryFieldToCategory($schema);
        $this->addManageInventoryFieldToProduct($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function addManageInventoryFieldToCategory(Schema $schema)
    {
        $table = $schema->getTable('orob2b_catalog_category');

        $table->addColumn('manage_inventory_fallback_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['manage_inventory_fallback_id'], 'UNIQ_FBD712DDA4E4A513');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_fallback_value'),
            ['manage_inventory_fallback_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addManageInventoryFieldToProduct($schema)
    {
        $table = $schema->getTable('orob2b_product');
        $table->addColumn('manage_inventory_fallback_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['manage_inventory_fallback_id'], 'UNIQ_5F9796C9A4E4A513');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_entity_fallback_value'),
            ['manage_inventory_fallback_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => 'CASCADE']
        );
    }
}
