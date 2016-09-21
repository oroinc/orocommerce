<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddSalesRepresentativesTables implements Migration
{
    const ORO_USER_TABLE_NAME = 'oro_user';

    const ORO_B2B_ACCOUNT_TABLE_NAME = 'orob2b_account';
    const ORO_B2B_ACCOUNT_USER_TABLE_NAME = 'orob2b_account_user';

    const ORO_B2B_ACCOUNT_SALES_REPRESENTATIVES_TABLE_NAME = 'orob2b_account_sales_reps';
    const ORO_B2B_ACCOUNT_USER_SALES_REPRESENTATIVES_TABLE_NAME = 'orob2b_account_user_sales_reps';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroAccountSalesRepresentativesTable($schema);
        $this->createOroAccountUserSalesRepresentativesTable($schema);

        /** Foreign keys generation **/
        $this->addOroAccountSalesRepresentativesForeignKeys($schema);
        $this->addOroAccountUserSalesRepresentativesForeignKeys($schema);
    }

    /**
     * Create orob2b_account_sales_representatives table
     *
     * @param Schema $schema
     */
    protected function createOroAccountSalesRepresentativesTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addColumn('account_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['account_id', 'user_id']);
    }

    /**
     * Create orob2b_account_user_sales_representatives table
     *
     * @param Schema $schema
     */
    protected function createOroAccountUserSalesRepresentativesTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_USER_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addColumn('account_user_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['account_user_id', 'user_id']);
    }

    /**
     * Add orob2b_account_sales_representatives foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountSalesRepresentativesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_SALES_REPRESENTATIVES_TABLE_NAME);
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
     * Add orob2b_account_user_sales_representatives foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountUserSalesRepresentativesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_USER_SALES_REPRESENTATIVES_TABLE_NAME);
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
