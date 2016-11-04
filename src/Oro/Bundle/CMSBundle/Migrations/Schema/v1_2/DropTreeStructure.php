<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CMSBundle\Entity\Page;
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

        $queries->addQuery(new DropEntityConfigFieldQuery(Page::class, 'parentPage'));
        $queries->addQuery(new DropEntityConfigFieldQuery(Page::class, 'childPages'));
        $queries->addQuery(new DropEntityConfigFieldQuery(Page::class, 'root'));
        $queries->addQuery(new DropEntityConfigFieldQuery(Page::class, 'right'));
        $queries->addQuery(new DropEntityConfigFieldQuery(Page::class, 'level'));
        $queries->addQuery(new DropEntityConfigFieldQuery(Page::class, 'left'));
    }

    /**
     * @param Schema $schema
     */
    protected function dropTreeColumns(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_page');

        if ($table->hasForeignKey('fk_oro_cms_page_parent_id')) {
            $table->removeForeignKey('fk_oro_cms_page_parent_id');
        } elseif ($table->hasForeignKey('fk_orob2b_cms_page_parent_id')) {
            $table->removeForeignKey('fk_orob2b_cms_page_parent_id');
        }

        $table->dropColumn('parent_id');
        $table->dropColumn('tree_left');
        $table->dropColumn('tree_level');
        $table->dropColumn('tree_right');
        $table->dropColumn('tree_root');
    }
}
