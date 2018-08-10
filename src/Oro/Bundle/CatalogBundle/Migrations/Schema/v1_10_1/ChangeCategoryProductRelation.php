<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_10_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeCategoryProductRelation implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(new UpdateCategoryProductRelationFetchModeQuery());
    }
}
