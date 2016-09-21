<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroCustomerBundle implements Migration
{
    const ORO_B2B_PRODUCT_VISIBILITY_RESOLVED = 'orob2b_prod_vsb_resolv';
    const ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED = 'orob2b_acc_grp_prod_vsb_resolv';
    const ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED = 'orob2b_acc_prod_vsb_resolv';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroProductVisibilityResolvedTable($schema);
        $this->createOroAccountGroupProductVisibilityResolvedTable($schema);
        $this->createOroAccountProductVisibilityResolvedTable($schema);

        /** Foreign keys generation **/
        $this->addOroProductVisibilityResolvedForeignKeys($schema);
        $this->addOroAccountGroupProductVisibilityResolvedForeignKeys($schema);
        $this->addOroAccountProductVisibilityResolvedForeignKeys($schema);


        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\CustomerBundle\Entity\Account',
                'internal_rating',
                'dataaudit',
                'auditable',
                true
            )
        );
    }

    /**
     * Create orob2b_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_PRODUCT_VISIBILITY_RESOLVED);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['website_id', 'product_id']);
    }

    /**
     * Create orob2b_acc_grp_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroAccountGroupProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_group_id', 'website_id', 'product_id']);
    }

    /**
     * Create orob2b_acc_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroAccountProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED);
        $table->addColumn('account_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_id', 'website_id', 'product_id']);
    }

    /**
     * Add orob2b_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroProductVisibilityResolvedForeignKeys(Schema $schema)
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
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_grp_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountGroupProductVisibilityResolvedForeignKeys(Schema $schema)
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
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountProductVisibilityResolvedForeignKeys(Schema $schema)
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
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
