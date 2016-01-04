<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroB2BAccountBundle implements Migration
{
    const ORO_USER_TABLE_NAME = 'oro_user';

    const ORO_B2B_ACCOUNT_TABLE_NAME = 'orob2b_account';
    const ORO_B2B_ACCOUNT_USER_TABLE_NAME = 'orob2b_account_user';

    const ORO_B2B_ACCOUNT_SALE_REP_TABLE_NAME = 'orob2b_account_sale_rep';
    const ORO_B2B_ACCOUNT_USER_SALE_REP_TABLE_NAME = 'orob2b_account_user_sale_rep';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BAccountSaleRepTable($schema);
        $this->createOroB2BAccountUserSaleRepTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BAccountSaleRepForeignKeys($schema);
        $this->addOroB2BAccountUserSaleRepForeignKeys($schema);
    }

    /**
     * Create orob2b_account_sale_rep table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountSaleRepTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_SALE_REP_TABLE_NAME);
        $table->addColumn('account_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['account_id', 'user_id']);
    }

    /**
     * Create orob2b_account_user_sale_rep table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountUserSaleRepTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_USER_SALE_REP_TABLE_NAME);
        $table->addColumn('account_user_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['account_user_id', 'user_id']);
    }

    /**
     * Add orob2b_account_sale_rep foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountSaleRepForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_SALE_REP_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_USER_TABLE_NAME),
            ['user_id'],
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
     * Add orob2b_account_user_sale_rep foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserSaleRepForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_USER_SALE_REP_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_USER_TABLE_NAME),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_ACCOUNT_USER_TABLE_NAME),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
