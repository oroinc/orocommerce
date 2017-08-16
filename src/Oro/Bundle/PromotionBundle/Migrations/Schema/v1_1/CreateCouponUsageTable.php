<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateCouponUsageTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroPromotionCouponUsageTable($schema);

        /** Foreign keys generation **/
        $this->addOroPromotionCouponUsageForeignKeys($schema);
    }

    /**
     * Create oro_promotion_coupon_usage table
     *
     * @param Schema $schema
     */
    protected function createOroPromotionCouponUsageTable(Schema $schema)
    {
        $table = $schema->createTable('oro_promotion_coupon_usage');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('promotion_id', 'integer', ['notnull' => true]);
        $table->addColumn('coupon_id', 'integer', ['notnull' => true]);
        $table->addColumn('customer_user_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_promotion_coupon_usage foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPromotionCouponUsageForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_coupon_usage');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_promotion'),
            ['promotion_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_promotion_coupon'),
            ['coupon_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
