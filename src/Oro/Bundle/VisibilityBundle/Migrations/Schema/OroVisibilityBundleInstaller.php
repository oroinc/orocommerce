<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OroVisibilityBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_2';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
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
     */
    private function createOroProductVisibilityResolvedTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_prod_vsb_resolv');
        $table->addColumn('scope_id', 'integer');
        $table->addColumn('product_id', 'integer');
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['scope_id', 'product_id']);
    }

    /**
     * Create oro_cus_grp_prod_vsb_resolv table
     */
    private function createOroAccountGroupProductVisibilityResolvedTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cus_grp_prod_vsb_resolv');
        $table->addColumn('scope_id', 'integer');
        $table->addColumn('product_id', 'integer');
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['scope_id', 'product_id']);
    }

    /**
     * Create oro_cus_prod_vsb_resolv table
     */
    private function createOroAccountProductVisibilityResolvedTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cus_prod_vsb_resolv');
        $table->addColumn('scope_id', 'integer');
        $table->addColumn('product_id', 'integer');
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['scope_id', 'product_id']);
    }

    /**
     * Create oro_ctgr_vsb_resolv table
     */
    private function createOroCategoryVisibilityResolvedTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_ctgr_vsb_resolv');
        $table->addColumn('source_category_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('scope_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['scope_id', 'category_id']);
    }

    /**
     * Create oro_cus_grp_ctgr_vsb_resolv table
     */
    private function createOroAccountGroupCategoryVisibilityResolvedTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cus_grp_ctgr_vsb_resolv');
        $table->addColumn('source_category_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('scope_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['scope_id', 'category_id']);
    }

    /**
     * Create oro_cus_ctgr_vsb_resolv table
     */
    private function createOroAccountCategoryVisibilityResolvedTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cus_ctgr_vsb_resolv');
        $table->addColumn('source_category_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('scope_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['scope_id', 'category_id']);
    }

    /**
     * Create oro_category_visibility table
     */
    private function createOroCategoryVisibilityTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_category_visibility');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('scope_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['category_id', 'scope_id'], 'oro_ctgr_vis_uidx');
    }

    /**
     * Create oro_cus_category_visibility table
     */
    private function createOroAccountCategoryVisibilityTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cus_category_visibility');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('scope_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['category_id', 'scope_id'], 'oro_cus_ctgr_vis_uidx');
    }

    /**
     * Create oro_cus_grp_ctgr_visibility table
     */
    private function createOroAccountGroupCategoryVisibilityTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cus_grp_ctgr_visibility');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('scope_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['category_id', 'scope_id'], 'oro_cus_grp_ctgr_vis_uidx');
    }

    /**
     * Create oro_product_visibility table
     */
    private function createOroProductVisibilityTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_visibility');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('scope_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'scope_id'], 'oro_prod_vis_uidx');
    }

    /**
     * Create oro_cus_product_visibility table
     */
    private function createOroAccountProductVisibilityTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cus_product_visibility');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('scope_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'scope_id'], 'oro_cus_prod_vis_uidx');
    }

    /**
     * Create oro_cus_grp_prod_visibility table
     */
    private function createOroAccountGroupProductVisibilityTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cus_grp_prod_visibility');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('scope_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'scope_id'], 'oro_cus_grp_prod_vis_uidx');
    }

    /**
     * Add oro_category_visibility foreign keys.
     */
    private function addOroCategoryVisibilityForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_category_visibility');
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
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_cus_category_visibility foreign keys.
     */
    private function addOroAccountCategoryVisibilityForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cus_category_visibility');
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
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_cus_grp_ctgr_visibility foreign keys.
     */
    private function addOroAccountGroupCategoryVisibilityForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cus_grp_ctgr_visibility');
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
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_product_visibility foreign keys.
     */
    private function addOroProductVisibilityForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_visibility');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
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
     * Add oro_cus_product_visibility foreign keys.
     */
    private function addOroAccountProductVisibilityForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cus_product_visibility');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
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
     * Add oro_cus_grp_prod_visibility foreign keys.
     */
    private function addOroAccountGroupProductVisibilityForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cus_grp_prod_visibility');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
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
     * Add oro_prod_vsb_resolv foreign keys.
     */
    private function addOroProductVisibilityResolvedForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_prod_vsb_resolv');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
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
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_cus_grp_prod_vsb_resolv foreign keys.
     */
    private function addOroAccountGroupProductVisibilityResolvedForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cus_grp_prod_vsb_resolv');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cus_grp_prod_visibility'),
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
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_cus_prod_vsb_resolv foreign keys.
     */
    private function addOroAccountProductVisibilityResolvedForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cus_prod_vsb_resolv');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cus_product_visibility'),
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
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_ctgr_vsb_resolv foreign keys.
     */
    private function addOroCategoryVisibilityResolvedForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_ctgr_vsb_resolv');
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
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_cus_grp_ctgr_vsb_resolv foreign keys.
     */
    private function addOroAccountGroupCategoryVisibilityResolvedForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cus_grp_ctgr_vsb_resolv');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cus_grp_ctgr_visibility'),
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
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_cus_ctgr_vsb_resolv foreign keys.
     */
    private function addOroAccountCategoryVisibilityResolvedForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cus_ctgr_vsb_resolv');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cus_category_visibility'),
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
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
