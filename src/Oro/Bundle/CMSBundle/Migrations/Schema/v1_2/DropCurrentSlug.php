<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropCurrentSlug implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->dropCurrentSlugColumn($schema);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 15;
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function dropCurrentSlugColumn(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_page');
        $table->dropIndex('UNIQ_BCE4CB4A9B14E34B');
        $table->removeForeignKey('FK_BCE4CB4A9B14E34B');
        $table->dropColumn('current_slug_id');
    }
}
