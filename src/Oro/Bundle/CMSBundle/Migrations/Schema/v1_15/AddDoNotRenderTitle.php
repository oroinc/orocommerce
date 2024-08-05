<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDoNotRenderTitle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_cms_page');
        if (!$table->hasColumn('do_not_render_title')) {
            $table->addColumn('do_not_render_title', 'boolean', ['default' => false]);
        }
    }
}
