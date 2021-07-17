<?php

namespace Oro\Bundle\RuleBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroRuleBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroRuleTable($schema);

        /** Foreign keys generation **/
    }

    /**
     * Create oro_rule table
     */
    protected function createOroRuleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'text', []);
        $table->addColumn('enabled', 'boolean', ['default' => '1']);
        $table->addColumn('sort_order', 'integer', []);
        $table->addColumn('stop_processing', 'boolean', ['default' => '0']);
        $table->addColumn('expression', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_oro_rule_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_rule_updated_at', []);
    }
}
