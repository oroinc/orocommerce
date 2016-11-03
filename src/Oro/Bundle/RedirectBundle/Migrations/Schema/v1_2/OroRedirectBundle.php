<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroRedirectBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroRedirectTable($schema);
        $this->createOroSlugRedirectTable($schema);
        $this->createOroRedirectWebsiteTable($schema);

        /** Foreign keys generation **/
        $this->addOroSlugRedirectForeignKeys($schema);
    }

    /**
     * Create orob2b_redirect table
     *
     * @param Schema $schema
     */
    protected function createOroRedirectTable(Schema $schema)
    {
        $table = $schema->createTable('oro_redirect');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('redirect_from', 'string', ['notnull' => true, 'length' => 1024]);
        $table->addColumn('redirect_to', 'string', ['notnull' => true, 'length' => 1024]);
        $table->addColumn('redirect_type', 'integer', ['notnull' => true, 'comment' => '(301 or 302)']);
        $table->addColumn('from_hash', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['from_hash'], 'idx_oro_redirect_from_hash', []);
    }

    /**
     * Create oro_slug_redirect table
     *
     * @param Schema $schema
     */
    protected function createOroSlugRedirectTable(Schema $schema)
    {
        $table = $schema->createTable('oro_slug_redirect');
        $table->addColumn('slug_id', 'integer', []);
        $table->addColumn('redirect_id', 'integer', []);
        $table->setPrimaryKey(['slug_id', 'redirect_id']);
        $table->addIndex(['slug_id'], 'IDX_DE8AE597311966CE', []);
        $table->addIndex(['redirect_id'], 'IDX_DE8AE597B42D874D', []);
    }

    /**
     * Create oro_redirect_website table
     *
     * @param Schema $schema
     */
    protected function createOroRedirectWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable('oro_redirect_website');
        $table->addColumn('website_id', 'integer', []);
        $table->setPrimaryKey(['website_id']);
    }

    /**
     * Add oro_slug_redirect foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroSlugRedirectForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_slug_redirect');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_redirect_slug'),
            ['slug_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_redirect'),
            ['redirect_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
