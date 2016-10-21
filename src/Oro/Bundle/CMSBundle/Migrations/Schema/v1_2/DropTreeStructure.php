<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropTreeStructure implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->dropTreeColumns($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function dropTreeColumns(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_page');

        $table->removeForeignKey('fk_oro_cms_page_parent_id');

        $table->dropColumn('parent_id');
        $table->dropColumn('tree_left');
        $table->dropColumn('tree_level');
        $table->dropColumn('tree_right');
        $table->dropColumn('tree_root');
    }
}
