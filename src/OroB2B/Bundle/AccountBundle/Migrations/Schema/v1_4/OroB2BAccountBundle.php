<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAccountBundle implements Migration, ExtendExtensionAwareInterface
{
    const ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_category_visibility';
    const ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_acc_category_visibility';
    const ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_acc_grp_ctgr_visibility';
    const ORO_B2B_CATEGORY_TABLE_NAME = 'orob2b_catalog_category';
    const ORO_B2B_ACCOUNT_TABLE_NAME = 'orob2b_account';
    const ORO_B2B_ACCOUNT_GROUP_TABLE_NAME = 'orob2b_account_group';

    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BCategoryVisibilityTable($schema);
        $this->createOroB2BAccountCategoryVisibilityTable($schema);
        $this->createOroB2BAccountGroupCategoryVisibilityTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BCategoryVisibilityForeignKeys($schema);
        $this->addOroB2BAccountCategoryVisibilityForeignKeys($schema);
        $this->addOroB2BAccountGroupCategoryVisibilityForeignKeys($schema);
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * Create orob2b_category_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $this->extendExtension->addEnumField(
            $schema,
            self::ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME,
            'visibility',
            'category_visibility'
        );
    }

    /**
     * Create orob2b_acc_category_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $this->extendExtension->addEnumField(
            $schema,
            self::ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME,
            'visibility',
            'acc_ctgry_visibility'
        );
    }

    /**
     * Create orob2b_acc_grp_ctgr_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountGroupCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $this->extendExtension->addEnumField(
            $schema,
            self::ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME,
            'visibility',
            'acc_grp_ctgry_vsblity'
        );
    }

    /**
     * Add orob2b_category_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCategoryVisibilityForeignKeys(Schema $schema)
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
    protected function addOroB2BAccountCategoryVisibilityForeignKeys(Schema $schema)
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
    protected function addOroB2BAccountGroupCategoryVisibilityForeignKeys(Schema $schema)
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
}
