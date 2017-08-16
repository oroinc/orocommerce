<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateCouponTable implements Migration, ExtendExtensionAwareInterface
{
    const ORDER_COUPONS_RELATION_NAME = 'appliedCoupons';

    /**
     * @var ExtendExtension
     */
    private $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroPromotionCouponTable($schema);
        $this->addOroPromotionCouponForeignKeys($schema);
        $this->addCouponsRelationToOrders($schema);
    }

    /**
     * Create oro_promotion_coupon table
     *
     * @param Schema $schema
     */
    protected function createOroPromotionCouponTable(Schema $schema)
    {
        $table = $schema->createTable('oro_promotion_coupon');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('promotion_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('total_uses', 'integer', ['default' => '0']);
        $table->addColumn('uses_per_coupon', 'integer', ['notnull' => false, 'default' => '1']);
        $table->addColumn('uses_per_person', 'integer', ['notnull' => false, 'default' => '1']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('valid_until', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addUniqueIndex(['code']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_oro_promotion_coupon_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_promotion_coupon_updated_at', []);
    }

    /**
     * Add oro_promotion_coupon foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPromotionCouponForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_coupon');
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

    /**
     * @param Schema $schema
     */
    protected function addCouponsRelationToOrders(Schema $schema)
    {
        $targetTable = $schema->getTable('oro_order');
        $couponTable = $schema->getTable('oro_promotion_coupon');
        $targetTitleColumnNames = $targetTable->getPrimaryKeyColumns();

        $this->extendExtension->addManyToManyRelation(
            $schema,
            $targetTable,
            self::ORDER_COUPONS_RELATION_NAME,
            $couponTable,
            $targetTitleColumnNames,
            $targetTitleColumnNames,
            $targetTitleColumnNames,
            [
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['all'],
                    'without_default' => true
                ],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false],
            ]
        );
    }
}
