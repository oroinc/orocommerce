<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ReorganizePageSlugs implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCmsPageSlugTable($schema);

        /** Foreign keys generation **/
        $this->addOroCmsPageSlugForeignKeys($schema);
        
        $queries->addQuery(new ReorganizeSlugsQuery());
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }
    
    /**
     * Create oro_cms_page_slug table
     *
     * @param Schema $schema
     */
    protected function createOroCmsPageSlugTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_page_slug');
        $table->addColumn('page_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['page_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add oro_cms_page_slug foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmsPageSlugForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_page_slug');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_page'),
            ['page_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
