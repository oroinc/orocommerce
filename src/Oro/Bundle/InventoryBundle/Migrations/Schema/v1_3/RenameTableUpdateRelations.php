<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;

class RenameTableUpdateRelations implements Migration, RenameExtensionAwareInterface
{
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
