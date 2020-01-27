<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CMSBundle\Migrations\Schema\v1_7\UpdateBrandDescriptionFieldDataQuery;
use Oro\Bundle\CMSBundle\Migrations\Schema\v1_7\UpdateCategoryDescriptionFieldDataQuery;
use Oro\Bundle\CMSBundle\Migrations\Schema\v1_7\UpdateProductDescriptionFieldDataQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCMSBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addPostQuery(new UpdateCategoryDescriptionFieldDataQuery());
        $queries->addPostQuery(new UpdateBrandDescriptionFieldDataQuery());
        $queries->addPostQuery(new UpdateProductDescriptionFieldDataQuery());
    }
}
