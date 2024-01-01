<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManagerAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManagerAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Excludes fields from category export.
 */
class ExcludeFieldsFromExport implements Migration, ExtendOptionsManagerAwareInterface
{
    use ExtendOptionsManagerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_catalog_category');
        $excludeFields = ['products', 'smallImage', 'largeImage'];
        foreach ($excludeFields as $name) {
            // Works in case when the affected relation does not yet exist.
            $this->extendOptionsManager->mergeColumnOptions(
                $table->getName(),
                $name,
                ['importexport' => ['excluded' => true]]
            );

            // Works in case when the affected field already exists.
            $queries->addPostQuery(
                new UpdateEntityConfigFieldValueQuery(Category::class, $name, 'importexport', 'excluded', true)
            );
        }
    }
}
