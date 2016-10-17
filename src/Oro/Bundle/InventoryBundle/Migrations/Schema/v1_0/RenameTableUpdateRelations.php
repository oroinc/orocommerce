<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameTableUpdateRelations implements Migration, RenameExtensionAwareInterface
{
    use MigrationConstraintTrait;

    const ORO_B2B_WAREHOUSE_INVENTORY_TABLE = 'orob2b_warehouse_inventory_lev';
    const OLD_WAREHOUSE_INVENTORY_TABLE = 'oro_warehouse_inventory_lev';
    const NEW_INVENTORY_TABLE = 'oro_inventory_level';

    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        // rename orob2b namespace
        if ($schema->hasTable(self::ORO_B2B_WAREHOUSE_INVENTORY_TABLE)) {
            $extension->renameTable($schema, $queries, self::ORO_B2B_WAREHOUSE_INVENTORY_TABLE, self::OLD_WAREHOUSE_INVENTORY_TABLE);
            $schema->getTable(self::ORO_B2B_WAREHOUSE_INVENTORY_TABLE)->dropIndex('uidx_orob2b_wh_wh_inventory_lev');
            $extension->addUniqueIndex(
                $schema,
                $queries,
                self::OLD_WAREHOUSE_INVENTORY_TABLE,
                ['warehouse_id', 'product_unit_precision_id'],
                'uidx_oro_wh_wh_inventory_lev'
            );
        }

        // drop warehouse indexes
        $schema->getTable(self::OLD_WAREHOUSE_INVENTORY_TABLE)->dropIndex('uidx_oro_wh_wh_inventory_lev');

        // drop warehouse column
        $inventoryTable = $schema->getTable(self::OLD_WAREHOUSE_INVENTORY_TABLE);
        $warehouseForeignKey = $this->getConstraintName($inventoryTable, 'warehouse_id');
        $inventoryTable->removeForeignKey($warehouseForeignKey);

        // rename entity
        $extension->renameTable($schema, $queries, self::OLD_WAREHOUSE_INVENTORY_TABLE, self::NEW_INVENTORY_TABLE);
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
