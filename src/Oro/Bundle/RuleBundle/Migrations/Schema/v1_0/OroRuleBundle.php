<?php

namespace Oro\Bundle\RuleBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroRuleBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroRuleTable($schema);
    }

    protected function createOroRuleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_rule');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'text', []);
        $table->addColumn('enabled', 'boolean', ['default' => true]);
        $table->addColumn('sort_order', 'integer', []);
        $table->addColumn('stop_processing', 'boolean', ['default' => false]);
        $table->addColumn('expression', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_oro_rule_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_rule_updated_at', []);
    }
}
