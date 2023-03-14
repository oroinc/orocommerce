<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroPromotionBundleInstaller implements
    Installation,
    ActivityExtensionAwareInterface,
    ExtendExtensionAwareInterface
{
    /**
     * @var ActivityExtension
     */
    private $activityExtension;

    /**
     * @var ExtendExtension
     */
    private $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_7';
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

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
        /** Tables generation **/
        $this->createOroPromotionTable($schema);
        $this->createOroPromotionCouponTable($schema);
        $this->createOroPromotionDescriptionTable($schema);
        $this->createOroPromotionDiscountConfigTable($schema);
        $this->createOroPromotionLabelTable($schema);
        $this->createOroPromotionScheduleTable($schema);
        $this->createOroPromotionScopeTable($schema);
        $this->createOroPromotionAppliedDiscountTable($schema);
        $this->createOroPromotionCouponUsageTable($schema);
        $this->createOroPromotionAppliedCouponTable($schema);
        $this->createOroPromotionAppliedTable($schema);

        /** Foreign keys generation **/
        $this->addOroPromotionForeignKeys($schema);
        $this->addOroPromotionCouponForeignKeys($schema);
        $this->addOroPromotionDescriptionForeignKeys($schema);
        $this->addOroPromotionLabelForeignKeys($schema);
        $this->addOroPromotionScheduleForeignKeys($schema);
        $this->addOroPromotionScopeForeignKeys($schema);
        $this->addOroPromotionAppliedDiscountForeignKeys($schema);
        $this->addOroPromotionCouponUsageForeignKeys($schema);
        $this->addOroPromotionAppliedCouponForeignKeys($schema);

        $this->addActivityAssociations($schema);

        $this->addAppliedCouponsToOrder($schema);
        $this->addAppliedCouponsToCheckout($schema);
        $this->addAppliedPromotionsToOrder($schema);
        $this->addDisablePromotionsToOrder($schema);

        $this->addPromotionEntityConfigs($schema);
    }

    /**
     * Create oro_promotion table
     */
    protected function createOroPromotionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_promotion');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('discount_config_id', 'integer', []);
        $table->addColumn('rule_id', 'integer', []);
        $table->addColumn('products_segment_id', 'integer', []);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('use_coupons', 'boolean', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['discount_config_id']);
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
        $table->addColumn('enabled', 'boolean', ['default' => false]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('code_uppercase', 'string', ['length' => 255]);
        $table->addColumn('uses_per_coupon', 'integer', ['notnull' => false, 'default' => '1']);
        $table->addColumn('uses_per_person', 'integer', ['notnull' => false, 'default' => '1']);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('valid_from', 'datetime', ['notnull' => false]);
        $table->addColumn('valid_until', 'datetime', ['notnull' => false]);
        $table->addUniqueIndex(['code']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_oro_promotion_coupon_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_promotion_coupon_updated_at', []);
        $table->addIndex(['code_uppercase'], 'idx_oro_promotion_coupon_code_upper', []);
    }

    /**
     * Create oro_promotion_description table
     */
    protected function createOroPromotionDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_promotion_description');
        $table->addColumn('promotion_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['promotion_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create oro_promotion_discount_config table
     */
    protected function createOroPromotionDiscountConfigTable(Schema $schema)
    {
        $table = $schema->createTable('oro_promotion_discount_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type', 'string', ['length' => 50]);
        $table->addColumn('options', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['type'], 'oro_promo_discount_type');
    }

    /**
     * Create oro_promotion_label table
     */
    protected function createOroPromotionLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_promotion_label');
        $table->addColumn('promotion_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['promotion_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create oro_promotion_schedule table
     */
    protected function createOroPromotionScheduleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_promotion_schedule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('promotion_id', 'integer', ['notnull' => false]);
        $table->addColumn('active_at', 'datetime', ['notnull' => false]);
        $table->addColumn('deactivate_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_promotion_scope table
     */
    protected function createOroPromotionScopeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_promotion_scope');
        $table->addColumn('promotion_id', 'integer', []);
        $table->addColumn('scope_id', 'integer', []);
        $table->setPrimaryKey(['promotion_id', 'scope_id']);
    }

    /**
     * Create oro_promotion_applied_discount table
     */
    protected function createOroPromotionAppliedDiscountTable(Schema $schema)
    {
        $table = $schema->createTable('oro_promotion_applied_discount');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('line_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('applied_promotion_id', 'integer', []);
        $table->addColumn('amount', 'money_value', [
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money_value)',
        ]);
        $table->addColumn('currency', 'currency', ['length' => 3, 'comment' => '(DC2Type:currency)']);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
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
        $table->addColumn('config_options', 'json_array', ['comment' => '(DC2Type:json_array)']);
        $table->addColumn('promotion_data', 'json_array', ['comment' => '(DC2Type:json_array)']);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_promotion foreign keys.
     */
    protected function addOroPromotionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_promotion_discount_config'),
            ['discount_config_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rule'),
            ['rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_segment'),
            ['products_segment_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
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
     * Add oro_promotion_description foreign keys.
     */
    protected function addOroPromotionDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_description');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_promotion'),
            ['promotion_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_promotion_label foreign keys.
     */
    protected function addOroPromotionLabelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_promotion'),
            ['promotion_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_promotion_schedule foreign keys.
     */
    protected function addOroPromotionScheduleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_schedule');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_promotion'),
            ['promotion_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_promotion_scope foreign keys.
     */
    protected function addOroPromotionScopeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_scope');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_promotion'),
            ['promotion_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_promotion_applied_discount foreign keys.
     */
    protected function addOroPromotionAppliedDiscountForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_applied_discount');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order_line_item'),
            ['line_item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_promotion_applied'),
            ['applied_promotion_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
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
     * Add oro_promotion_applied_coupon foreign keys.
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

    protected function addActivityAssociations(Schema $schema)
    {
        $this->activityExtension->addActivityAssociation($schema, 'oro_note', 'oro_promotion');
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

    protected function addAppliedCouponsToCheckout(Schema $schema)
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            'oro_promotion_applied_coupon',
            'checkout',
            'oro_checkout',
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
            'checkout',
            'oro_checkout',
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
                    'on_delete' => 'CASCADE',
                    'cascade' => ['remove'],
                ],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false]
            ]
        );
    }

    protected function addDisablePromotionsToOrder(Schema $schema)
    {
        $table = $schema->getTable('oro_order');
        $table->addColumn(
            'disablePromotions',
            'boolean',
            [
                'oro_options' => [
                    'extend'    => ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM],
                    'dataaudit' => ['auditable' => false]
                ]
            ]
        );
    }

    public function addPromotionEntityConfigs(Schema $schema): void
    {
        $options = new OroOptions();
        $options->set('promotion', 'is_promotion_aware', true);
        $options->set('promotion', 'is_coupon_aware', true);
        $schema->getTable('oro_order')
               ->addOption(OroOptions::KEY, $options);

        $options = new OroOptions();
        $options->set('promotion', 'is_coupon_aware', true);
        $schema->getTable('oro_checkout')
            ->addOption(OroOptions::KEY, $options);
    }
}
