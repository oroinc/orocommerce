<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ConfigBundle\Migration\RenameConfigSectionQuery;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroOrderBundle implements Migration
{
    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroOrderShippingTrackingTable($schema);
        $this->addOroOrderShippingTrackingForeignKeys($schema);
    }

    /**
     * Create oro_order_shipping_tracking table
     *
     * @param Schema $schema
     */
    protected function createOroOrderShippingTrackingTable(Schema $schema)
    {
        $table = $schema->createTable('oro_order_shipping_tracking');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('order_id', 'integer', ['notnull' => true]);
        $table->addColumn('method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addIndex(['order_id'], 'IDX_F9A53B6A8D9F6D83', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_order_shipping_tracking foreign keys.
     *
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addOroOrderShippingTrackingForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_order_shipping_tracking');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
