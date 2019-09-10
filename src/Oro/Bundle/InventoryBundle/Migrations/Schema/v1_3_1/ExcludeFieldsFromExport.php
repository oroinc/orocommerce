<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_3_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Excludes fields from category export.
 */
class ExcludeFieldsFromExport implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        foreach (['availability_date'] as $fieldName) {
            $queries->addPostQuery(
                new UpdateEntityConfigFieldValueQuery(Category::class, $fieldName, 'importexport', 'excluded', true)
            );
        }
    }
}
