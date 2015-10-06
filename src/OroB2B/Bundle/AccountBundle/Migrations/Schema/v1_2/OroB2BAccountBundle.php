<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAccountBundle implements Migration
{
    const ORO_B2B_PRODUCT_VISIBILITY_TABLE_NAME = 'orob2b_product_visibility';
    const ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME = 'orob2b_acc_product_visibility';
    const ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME = 'orob2b_acc_grp_prod_visibility';
    const ORO_B2B_PRODUCT_TABLE_NAME = 'orob2b_product';
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
        $this->createOroB2BProductVisibilityTable($schema);
        $this->createOroB2BAccountProductVisibilityTable($schema);
        $this->createOroB2BAccountGroupProductVisibilityTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BProductVisibilityForeignKeys($schema);
        $this->addOroB2BAccountProductVisibilityForeignKeys($schema);
        $this->addOroB2BAccountGroupProductVisibilityForeignKeys($schema);
    }

    /**
     * Create orob2b_product_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroB2BProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_acc_product_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_acc_grp_prod_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountGroupProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_product_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_product_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_PRODUCT_TABLE_NAME),
            ['product_id'],
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
    protected function addOroB2BAccountGroupProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_PRODUCT_TABLE_NAME),
            ['product_id'],
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
