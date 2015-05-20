<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroB2BSaleBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BSaleQuoteTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BSaleQuoteForeignKeys($schema);
    }

    /**
     * Create orob2b_sale_quote table
     *
     * @param Schema $schema
     */
    protected function createOrob2BSaleQuoteTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_sale_quote');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('qid', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('valid_until', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_owner_id'], 'IDX_4F66B6F69EB185F9', []);
    }

    /**
     * Add orob2b_sale_quote foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BSaleQuoteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
