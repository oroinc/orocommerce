<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroB2BAccountBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Foreign keys generation **/
        $this->createOrob2BWindowsStateTable($schema);

        /** Tables generation **/
        $this->addOrob2BWindowsStateForeignKeys($schema);
    }

    /**
     * Create orob2b_windows_state table
     *
     * @param Schema $schema
     */
    protected function createOrob2BWindowsStateTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_windows_state');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_user_id', 'integer', []);
        $table->addColumn('data', 'text', []);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['customer_user_id'], 'orob2b_windows_st_cust_user_idx', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_windows_state foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BWindowsStateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_windows_state');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
