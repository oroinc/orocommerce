<?php

namespace Oro\Bundle\WebsiteSearchBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Remove unused title field
 */
class RemoveTitleField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_website_search_item');
        if ($table->hasColumn('title')) {
            $table->dropColumn('title');
        }
    }
}
