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

        $toTable = self::INVENTORY_LEVEL_TABLE_NAME;
        $fromTable = self::OLD_WAREHOUSE_INVENTORY_TABLE;
        $indexToDrop = 'uidx_oro_wh_wh_inventory_lev';

        if ($schema->hasTable(self::ORO_B2B_WAREHOUSE_INVENTORY_TABLE)) {
            $fromTable = self::ORO_B2B_WAREHOUSE_INVENTORY_TABLE;
            $indexToDrop = 'uidx_orob2b_wh_wh_inventory_lev';
        }

        //rename table
        $extension->renameTable($schema, $queries, $fromTable, $toTable);

        $inventoryTable = $schema->getTable($fromTable);

        // drop warehouse indexes
        $inventoryTable->dropIndex($indexToDrop);

        // drop warehouse column
        $warehouseForeignKey = $this->getConstraintName($inventoryTable, 'warehouse_id');
        $inventoryTable->removeForeignKey($warehouseForeignKey);
        $inventoryTable->dropColumn('warehouse_id');
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
