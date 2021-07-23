<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ModifyTableStructure implements
    Migration,
    DatabasePlatformAwareInterface,
    ExtendExtensionAwareInterface,
    OrderedMigrationInterface
{
    use DatabasePlatformAwareTrait;

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
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroPromotionAppliedCouponTable($schema);
        $this->createOroPromotionAppliedTable($schema);
        $this->createOroPromotionCouponTable($schema);
        $this->createOroPromotionCouponUsageTable($schema);
        $this->modifyAppliedDiscountTable($schema);

        $this->addOroPromotionAppliedCouponForeignKeys($schema);
        $this->addOroPromotionCouponForeignKeys($schema);
        $this->addOroPromotionCouponUsageForeignKeys($schema);

        $this->addAppliedCouponsToOrder($schema);
        $this->addAppliedPromotionsToOrder($schema);

        $queries->addPostQuery(new MigratePromotionDataQuery());
    }

    /**
     * Create oro_promotion_applied_coupon table
     */
    protected function createOroPromotionAppliedCouponTable(Schema $schema)
    {
        $table = $schema->createTable('oro_promotion_applied_coupon');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('applied_promotion_id', 'integer', ['notnull' => false]);
        $table->addColumn('coupon_code', 'string', ['length' => 255]);
        $table->addColumn('source_promotion_id', 'integer');
        $table->addColumn('source_coupon_id', 'integer');
        $table->addColumn('created_at', 'datetime', []);
        $table->addUniqueIndex(['applied_promotion_id']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_promotion_applied table
     */
    protected function createOroPromotionAppliedTable(Schema $schema)
    {
        $table = $schema->createTable('oro_promotion_applied');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('source_promotion_id', 'integer');
        $table->addColumn('active', 'boolean', ['default' => '1']);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('promotion_name', 'text', []);
        $table->addColumn('config_options', 'json_array', []);
        $table->addColumn('promotion_data', 'json_array', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_promotion_coupon table
     */
    protected function createOroPromotionCouponTable(Schema $schema)
    {
        $table = $schema->createTable('oro_promotion_coupon');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('promotion_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 255]);
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
     * Create oro_promotion_coupon_usage table
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

    protected function modifyAppliedDiscountTable(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_applied_discount');
        $table->addColumn('applied_promotion_id', 'integer', ['notnull' => false]);
    }

    /**
     * Add oro_promotion_coupon_usage foreign keys.
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

    /**
     * Add oro_promotion_coupon foreign keys.
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
     * Add oro_promotion_applied foreign keys.
     */
    protected function addOroPromotionAppliedCouponForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_applied_coupon');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_promotion_applied'),
            ['applied_promotion_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    protected function addAppliedCouponsToOrder(Schema $schema)
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            'oro_promotion_applied_coupon',
            'order',
            'oro_order',
            'id',
            [
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'on_delete' => 'CASCADE',
                ],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false]
            ]
        );

        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            'oro_promotion_applied_coupon',
            'order',
            'oro_order',
            'appliedCoupons',
            ['coupon_code'],
            ['coupon_code'],
            ['coupon_code'],
            [
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'on_delete' => 'CASCADE'
                ],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false]
            ]
        );
    }

    protected function addAppliedPromotionsToOrder(Schema $schema)
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            'oro_promotion_applied',
            'order',
            'oro_order',
            'id',
            [
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'on_delete' => 'CASCADE',
                ],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false]
            ]
        );

        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            'oro_promotion_applied',
            'order',
            'oro_order',
            'appliedPromotions',
            ['promotion_name'],
            ['promotion_name'],
            ['promotion_name'],
            [
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'on_delete' => 'CASCADE'
                ],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false]
            ]
        );
    }
}
