<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAccountBundle implements Migration
{
    const ORO_B2B_PRODUCT_VISIBILITY_RESOLVED = 'orob2b_prod_vsb_resolv';
    const ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED = 'orob2b_acc_prod_vsb_resolv';
    const ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED = 'orob2b_acc_grp_prod_vsb_resolv';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BProductVisibilityResolvedTable($schema);
        $this->createOroB2BAccountProductVisibilityResolvedTable($schema);
        $this->createOroB2BAccountGroupProductVisibilityResolvedTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BProductVisibilityResolvedForeignKeys($schema);
        $this->addOroB2BAccountProductVisibilityResolvedForeignKeys($schema);
        $this->addOroB2BAccountGroupProductVisibilityResolvedForeignKeys($schema);
    }

    /**
     * Create orob2b_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroB2BProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_PRODUCT_VISIBILITY_RESOLVED);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'integer', ['notnull' => false]);
        $table->addColumn('source', 'integer', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['website_id', 'product_id']);
        $table->addIndex(['website_id'], 'IDX_ED62570618F45C82', []);
        $table->addIndex(['product_id'], 'IDX_ED6257064584665A', []);
    }

    /**
     * Create orob2b_acc_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'integer', ['notnull' => false]);
        $table->addColumn('source', 'integer', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_group_id', 'website_id', 'product_id']);
        $table->addIndex(['account_group_id'], 'IDX_41B7427A869A3BF1', []);
        $table->addIndex(['source_product_visibility'], 'IDX_41B7427A48B3F7B5', []);
        $table->addIndex(['website_id'], 'IDX_41B7427A18F45C82', []);
        $table->addIndex(['product_id'], 'IDX_41B7427A4584665A', []);
    }

    /**
     * Create orob2b_acc_grp_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountGroupProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'integer', ['notnull' => false]);
        $table->addColumn('source', 'integer', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_group_id', 'website_id', 'product_id']);
        $table->addIndex(['account_group_id'], 'IDX_417AC14F869A3BF1', []);
        $table->addIndex(['source_product_visibility'], 'IDX_417AC14F48B3F7B5', []);
        $table->addIndex(['website_id'], 'IDX_417AC14F18F45C82', []);
        $table->addIndex(['product_id'], 'IDX_417AC14F4584665A', []);
    }

    /**
     * Add orob2b_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BProductVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_PRODUCT_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_visibility'),
            ['source_product_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
    
    /**
     * Add orob2b_acc_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountProductVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_acc_product_visibility'),
            ['source_product_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_grp_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountGroupProductVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_acc_grp_prod_visibility'),
            ['source_product_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_group'),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
