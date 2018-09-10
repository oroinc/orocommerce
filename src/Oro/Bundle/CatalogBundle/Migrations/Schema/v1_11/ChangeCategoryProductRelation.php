<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Updates relation settings between Category and Product entities.
 */
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
