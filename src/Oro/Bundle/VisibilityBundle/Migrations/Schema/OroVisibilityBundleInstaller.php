<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AccountBundle\Migrations\Schema\OroAccountBundleInstaller;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OroVisibilityBundleInstaller implements Installation
{
    const ORO_CATEGORY_VISIBILITY_TABLE_NAME = 'oro_category_visibility';
    const ORO_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME = 'oro_acc_category_visibility';
    const ORO_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME = 'oro_acc_grp_ctgr_visibility';
    const ORO_CATEGORY_TABLE_NAME = 'oro_catalog_category';

    const ORO_PRODUCT_VISIBILITY_TABLE_NAME = 'oro_product_visibility';
    const ORO_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME = 'oro_acc_product_visibility';
    const ORO_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME = 'oro_acc_grp_prod_visibility';
    const ORO_PRODUCT_TABLE_NAME = 'oro_product';

    const ORO_PRODUCT_VISIBILITY_RESOLVED = 'oro_prod_vsb_resolv';
    const ORO_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED = 'oro_acc_grp_prod_vsb_resolv';
    const ORO_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED = 'oro_acc_prod_vsb_resolv';

    const ORO_CATEGORY_VISIBILITY_RESOLVED = 'oro_ctgr_vsb_resolv';
    const ORO_ACCOUNT_GROUP_CATEGORY_VISIBILITY_RESOLVED = 'oro_acc_grp_ctgr_vsb_resolv';
    const ORO_ACCOUNT_CATEGORY_VISIBILITY_RESOLVED = 'oro_acc_ctgr_vsb_resolv';

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * Sets the ExtendExtension
     *
     * @param ExtendExtension $extendExtension
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
        $this->createOroCategoryVisibilityTable($schema);
        $this->createOroAccountCategoryVisibilityTable($schema);
        $this->createOroAccountGroupCategoryVisibilityTable($schema);

        $this->createOroProductVisibilityTable($schema);
        $this->createOroAccountProductVisibilityTable($schema);
        $this->createOroAccountGroupProductVisibilityTable($schema);


        $this->createOroProductVisibilityResolvedTable($schema);
        $this->createOroAccountGroupProductVisibilityResolvedTable($schema);
        $this->createOroAccountProductVisibilityResolvedTable($schema);

        $this->createOroCategoryVisibilityResolvedTable($schema);
        $this->createOroAccountGroupCategoryVisibilityResolvedTable($schema);
        $this->createOroAccountCategoryVisibilityResolvedTable($schema);

        $this->addOroProductVisibilityForeignKeys($schema);
        $this->addOroAccountProductVisibilityForeignKeys($schema);
        $this->addOroAccountGroupProductVisibilityForeignKeys($schema);


        $this->addOroProductVisibilityResolvedForeignKeys($schema);
        $this->addOroAccountGroupProductVisibilityResolvedForeignKeys($schema);
        $this->addOroAccountProductVisibilityResolvedForeignKeys($schema);

        $this->addOroCategoryVisibilityForeignKeys($schema);
        $this->addOroAccountCategoryVisibilityForeignKeys($schema);
        $this->addOroAccountGroupCategoryVisibilityForeignKeys($schema);

        $this->addOroCategoryVisibilityResolvedForeignKeys($schema);
        $this->addOroAccountGroupCategoryVisibilityResolvedForeignKeys($schema);
        $this->addOroAccountCategoryVisibilityResolvedForeignKeys($schema);
    }

    /**
     * Create oro_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_PRODUCT_VISIBILITY_RESOLVED);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['website_id', 'product_id']);
    }

    /**
     * Create oro_acc_grp_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroAccountGroupProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_group_id', 'website_id', 'product_id']);
    }

    /**
     * Create oro_acc_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroAccountProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED);
        $table->addColumn('account_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_id', 'website_id', 'product_id']);
    }

    /**
     * Create oro_ctgr_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroCategoryVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_CATEGORY_VISIBILITY_RESOLVED);
        $table->addColumn('source_category_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['category_id']);
    }

    /**
     * Create oro_acc_grp_ctgr_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroAccountGroupCategoryVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_GROUP_CATEGORY_VISIBILITY_RESOLVED);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('source_category_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_group_id', 'category_id']);
    }

    /**
     * Create oro_acc_ctgr_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroAccountCategoryVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_CATEGORY_VISIBILITY_RESOLVED);
        $table->addColumn('account_id', 'integer', []);
        $table->addColumn('source_category_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_id', 'category_id']);
    }


    /**
     * Create oro_category_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('source_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['category_id'], 'oro_ctgr_vis_uidx');
    }

    /**
     * Create oro_acc_category_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroAccountCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('source_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['category_id', 'account_id'], 'oro_acc_ctgr_vis_uidx');
    }

    /**
     * Create oro_acc_grp_ctgr_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroAccountGroupCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('source_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['category_id', 'account_group_id'], 'oro_acc_grp_ctgr_vis_uidx');
    }

    /**
     * Create oro_product_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('source_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['website_id', 'product_id'], 'oro_prod_vis_uidx');
    }

    /**
     * Create oro_acc_product_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroAccountProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('source_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['website_id', 'product_id', 'account_id'], 'oro_acc_prod_vis_uidx');
    }

    /**
     * Create oro_acc_grp_prod_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroAccountGroupProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('source_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['website_id', 'product_id', 'account_group_id'], 'oro_acc_grp_prod_vis_uidx');
    }

    /**
     * Add oro_category_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCategoryVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_category_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountCategoryVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(OroAccountBundleInstaller::ORO_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_grp_ctgr_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountGroupCategoryVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(OroAccountBundleInstaller::ORO_ACCOUNT_GROUP_TABLE_NAME),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_product_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(OroAccountBundleInstaller::ORO_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_product_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(OroAccountBundleInstaller::ORO_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(OroAccountBundleInstaller::ORO_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_grp_prod_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountGroupProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(OroAccountBundleInstaller::ORO_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(OroAccountBundleInstaller::ORO_ACCOUNT_GROUP_TABLE_NAME),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroProductVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_PRODUCT_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_visibility'),
            ['source_product_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_grp_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountGroupProductVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_acc_grp_prod_visibility'),
            ['source_product_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_group'),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountProductVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_acc_product_visibility'),
            ['source_product_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_ctgr_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCategoryVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_CATEGORY_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_category_visibility'),
            ['source_category_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_grp_ctgr_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountGroupCategoryVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_GROUP_CATEGORY_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_acc_grp_ctgr_visibility'),
            ['source_category_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_group'),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_ctgr_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountCategoryVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_CATEGORY_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_acc_category_visibility'),
            ['source_category_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
