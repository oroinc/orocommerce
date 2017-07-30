<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroPromotionBundleInstaller implements Installation, ActivityExtensionAwareInterface
{
    /**
     * @var ActivityExtension
     */
    private $activityExtension;

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
    public function getMigrationVersion()
    {
        return 'v1_1';
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

        /** Foreign keys generation **/
        $this->addOroPromotionForeignKeys($schema);
        $this->addOroPromotionCouponForeignKeys($schema);
        $this->addOroPromotionDescriptionForeignKeys($schema);
        $this->addOroPromotionLabelForeignKeys($schema);
        $this->addOroPromotionScheduleForeignKeys($schema);
        $this->addOroPromotionScopeForeignKeys($schema);
        $this->addOroPromotionAppliedDiscountForeignKeys($schema);

        $this->addActivityAssociations($schema);
    }

    /**
     * Create oro_promotion table
     *
     * @param Schema $schema
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
        $table->addColumn('uses_per_user', 'integer', ['notnull' => false, 'default' => '1']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('valid_until', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addUniqueIndex(['code']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_oro_promotion_coupon_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_promotion_coupon_updated_at', []);
    }

    /**
     * Create oro_promotion_description table
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
     */
    protected function createOroPromotionAppliedDiscountTable(Schema $schema)
    {
        $table = $schema->createTable('oro_promotion_applied_discount');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('promotion_id', 'integer', ['notnull' => false]);
        $table->addColumn('line_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('order_id', 'integer', ['notnull' => false]);
        $table->addColumn('amount', 'money_value', [
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money_value)',
        ]);
        $table->addColumn('currency', 'currency', ['length' => 3, 'comment' => '(DC2Type:currency)']);
        $table->addColumn('config_options', 'json_array', []);
        $table->addColumn('promotion_name', 'text', []);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_promotion foreign keys.
     *
     * @param Schema $schema
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
     * Add oro_promotion_description foreign keys.
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
     */
    protected function addOroPromotionAppliedDiscountForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_applied_discount');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_promotion'),
            ['promotion_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order_line_item'),
            ['line_item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addActivityAssociations(Schema $schema)
    {
        $this->activityExtension->addActivityAssociation($schema, 'oro_note', 'oro_promotion');
    }
}
