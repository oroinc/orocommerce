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
        return 'v1_0';
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
        $this->createOroPromotionToCouponTable($schema);

        /** Foreign keys generation **/
        $this->addOroPromotionForeignKeys($schema);
        $this->addOroPromotionDescriptionForeignKeys($schema);
        $this->addOroPromotionLabelForeignKeys($schema);
        $this->addOroPromotionScheduleForeignKeys($schema);
        $this->addOroPromotionScopeForeignKeys($schema);
        $this->addOroPromotionToCouponForeignKeys($schema);
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
        $table->addIndex(['rule_id']);
        $table->addIndex(['discount_config_id']);
        $table->addIndex(['products_segment_id']);
        $table->addIndex(['user_owner_id']);
        $table->addIndex(['organization_id']);
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
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
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
        $table->addIndex(['promotion_id']);
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
        $table->addIndex(['promotion_id']);
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
        $table->addIndex(['promotion_id']);
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
        $table->addIndex(['promotion_id']);
        $table->addIndex(['scope_id']);
    }

    /**
     * Create oro_promotion_to_coupon table
     *
     * @param Schema $schema
     */
    protected function createOroPromotionToCouponTable(Schema $schema)
    {
        $table = $schema->createTable('oro_promotion_to_coupon');
        $table->addColumn('promotion_id', 'integer', []);
        $table->addColumn('coupon_id', 'integer', []);
        $table->setPrimaryKey(['promotion_id', 'coupon_id']);
        $table->addIndex(['promotion_id']);
        $table->addIndex(['coupon_id']);
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
     * Add oro_promotion_to_coupon foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPromotionToCouponForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_to_coupon');
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
    }

    /**
     * @param Schema $schema
     */
    protected function addActivityAssociations(Schema $schema)
    {
        $this->activityExtension->addActivityAssociation($schema, 'oro_note', 'oro_promotion');
    }
}
