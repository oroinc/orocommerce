<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAccountBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->alterAccountUserSettingsTable($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws SchemaException
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
}
