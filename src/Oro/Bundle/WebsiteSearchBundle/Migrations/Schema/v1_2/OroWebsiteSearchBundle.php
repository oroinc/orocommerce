<?php

namespace Oro\Bundle\WebsiteSearchBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWebsiteSearchBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_website_search_decimal');
        $table->changeColumn('value', ['precision' => 21, 'scale' => 6]);
    }
}
