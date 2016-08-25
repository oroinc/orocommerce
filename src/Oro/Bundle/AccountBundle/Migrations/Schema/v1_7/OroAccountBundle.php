<?php

namespace Oro\Bundle\AccountBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAccountBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->alterAccountUserSettingsTable($schema);
        $this->addAccountUserWebsiteField($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function alterAccountUserSettingsTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_account_user_settings');

        $table->getColumn('currency')->setOptions(['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
            'fk_localization_id'
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addAccountUserWebsiteField(Schema $schema)
    {
        $table = $schema->getTable('orob2b_account_user');

        $table->addColumn('website_id', 'integer', ['notnull' => false]);

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
