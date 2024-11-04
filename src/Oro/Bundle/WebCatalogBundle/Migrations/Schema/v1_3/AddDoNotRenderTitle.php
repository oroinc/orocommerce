<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDoNotRenderTitle implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_web_catalog_variant');
        if (!$table->hasColumn('do_not_render_title')) {
            $table->addColumn('do_not_render_title', 'boolean', ['default' => false]);
        }
    }
}
