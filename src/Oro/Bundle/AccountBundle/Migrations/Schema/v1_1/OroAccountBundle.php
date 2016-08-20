<?php

namespace Oro\Bundle\AccountBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\AccountBundle\Migrations\Schema\OroAccountBundleInstaller;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroAccountBundle implements Migration
{
    const ORO_B2B_ACCOUNT_TABLE_NAME = 'orob2b_account';
    const ORO_B2B_ACCOUNT_GROUP_TABLE_NAME = 'orob2b_account_group';

    const ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_category_visibility';
    const ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_acc_category_visibility';
    const ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_acc_grp_ctgr_visibility';
    const ORO_B2B_CATEGORY_TABLE_NAME = 'orob2b_catalog_category';

    const ORO_B2B_PRODUCT_VISIBILITY_TABLE_NAME = 'orob2b_product_visibility';
    const ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME = 'orob2b_acc_product_visibility';
    const ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME = 'orob2b_acc_grp_prod_visibility';
    const ORO_B2B_PRODUCT_TABLE_NAME = 'orob2b_product';
    const ORO_B2B_WEBSITE_TABLE_NAME = 'orob2b_website';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCategoryVisibilityTable($schema);
        $this->createOroAccountCategoryVisibilityTable($schema);
        $this->createOroAccountGroupCategoryVisibilityTable($schema);

        $this->createOroProductVisibilityTable($schema);
        $this->createOroAccountProductVisibilityTable($schema);
        $this->createOroAccountGroupProductVisibilityTable($schema);

        /** Foreign keys generation **/
        $this->addOroCategoryVisibilityForeignKeys($schema);
        $this->addOroAccountCategoryVisibilityForeignKeys($schema);
        $this->addOroAccountGroupCategoryVisibilityForeignKeys($schema);

        $this->addOroProductVisibilityForeignKeys($schema);
        $this->addOroAccountProductVisibilityForeignKeys($schema);
        $this->addOroAccountGroupProductVisibilityForeignKeys($schema);

        $this->updateAuditAndRoleTables($schema);

        //Update Account User Role Table
        $table = $schema->getTable(OroAccountBundleInstaller::ORO_B2B_ACCOUNT_USER_ROLE_TABLE_NAME);
        $table->getColumn('role')->setType(Type::getType(Type::STRING))->setOptions(['length' => 255]);
        $table->getColumn('label')->setType(Type::getType(Type::STRING))->setOptions(['length' => 255]);
    }

    /**
     * @param Schema $schema
     */
    protected function updateAuditAndRoleTables(Schema $schema)
    {
        $schema->dropTable('orob2b_audit_field');
        $schema->dropTable('orob2b_audit');

        $auditTable = $schema->getTable('oro_audit');
        $auditTable->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $auditTable
            ->addForeignKeyConstraint(
                $schema->getTable('orob2b_account_user'),
                ['account_user_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null]
            );
    }

    /**
     * Create orob2b_category_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_acc_category_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroAccountCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_acc_grp_ctgr_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroAccountGroupCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_product_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_acc_product_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroAccountProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_acc_grp_prod_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroAccountGroupProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_category_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCategoryVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_category_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountCategoryVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_grp_ctgr_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountGroupCategoryVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_TABLE_NAME),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_product_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_product_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_grp_prod_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountGroupProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_TABLE_NAME),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
