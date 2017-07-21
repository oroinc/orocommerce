<?php

namespace Oro\Bundle\CouponBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\CouponBundle\Migrations\Schema\OroCouponBundleInstaller;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCouponBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroCouponTable($schema);
        $this->addOroCouponForeignKeys($schema);
    }

    /**
     * Create oro_coupon table
     *
     * @param Schema $schema
     */
    protected function createOroCouponTable(Schema $schema)
    {
        $table = $schema->createTable('oro_coupon');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('promotion_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('total_uses', 'integer', ['default' => '0']);
        $table->addColumn('uses_per_coupon', 'integer', ['notnull' => false]);
        $table->addColumn('uses_per_user', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addUniqueIndex(['code']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_oro_coupon_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_coupon_updated_at', []);
    }

    /**
     * Add oro_coupon foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCouponForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_coupon');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_promotion'),
            ['promotion_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
