<?php

namespace Oro\Bundle\WarehouseBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;

class RenameTablesAndColumns implements Migration, RenameExtensionAwareInterface
{
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

        // notes
        $notes = $schema->getTable('oro_note');

        $notes->removeForeignKey('FK_BA066CE18E2ECC08');
        $extension->renameColumn($schema, $queries, $notes, 'warehouse_6eca7547_id', 'warehouse_c913b87_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'orob2b_warehouse',
            ['warehouse_c913b87_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\WarehouseProBundle\Entity\Warehouse',
            'warehouse_6eca7547',
            'warehouse_c913b87',
            RelationType::MANY_TO_ONE
        ));

        // rename entities
        $extension->renameTable($schema, $queries, 'orob2b_warehouse', 'oro_warehouse');
        $extension->renameTable($schema, $queries, 'orob2b_warehouse_inventory_lev', 'oro_warehouse_inventory_lev');

        // rename indexes
        $schema->getTable('orob2b_warehouse')->dropIndex('idx_orob2b_warehouse_created_at');
        $schema->getTable('orob2b_warehouse')->dropIndex('idx_orob2b_warehouse_updated_at');
        $schema->getTable('orob2b_warehouse_inventory_lev')->dropIndex('uidx_orob2b_wh_wh_inventory_lev');

        $extension->addIndex($schema, $queries, 'oro_warehouse', ['created_at'], 'idx_oro_warehouse_created_at');
        $extension->addIndex($schema, $queries, 'oro_warehouse', ['updated_at'], 'idx_oro_warehouse_updated_at');
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_warehouse_inventory_lev',
            ['warehouse_id', 'product_unit_precision_id'],
            'uidx_oro_wh_wh_inventory_lev'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
