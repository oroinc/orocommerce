<?php

namespace OroB2B\Bundle\FallbackBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BFallbackBundleInstaller implements Installation
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
        $this->createOrob2BFallbackLocalizedValueTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BFallbackLocalizedValueForeignKeys($schema);
    }

    /**
     * Create orob2b_fallback_locale_value table
     *
     * @param Schema $schema
     */
    protected function createOrob2BFallbackLocalizedValueTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_fallback_locale_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('locale_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_orob2b_fallback_fallback', []);
        $table->addIndex(['string'], 'idx_orob2b_fallback_string', []);
    }

    /**
     * Add orob2b_fallback_locale_value foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BFallbackLocalizedValueForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_fallback_locale_value');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_locale'),
            ['locale_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
