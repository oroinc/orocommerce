<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAccountBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addAccountUserCurrencyTable($schema);
    }

    /**
    * @param Schema $schema
    *
    * @throws \Doctrine\DBAL\Schema\SchemaException
    */
    protected function addAccountUserCurrencyTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_account_user_currency');

        $table->addColumn('account_user_id', 'integer', ['length' => 255]);
        $table->addColumn('website_id', 'integer', ['length' => 255]);
        $table->addColumn('currency', 'string', ['length' => 3]);

        $table->setPrimaryKey(['account_user_id', 'website_id']);

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_account_user_id'
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_website_id'
        );
    }
}
