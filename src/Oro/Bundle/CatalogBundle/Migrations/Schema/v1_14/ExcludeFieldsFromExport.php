<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Migrations\Schema\OroCatalogBundleInstaller;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Excludes fields from category export.
 */
class ExcludeFieldsFromExport implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable(OroCatalogBundleInstaller::ORO_CATALOG_CATEGORY_TABLE_NAME);

        $excludeFields = [
            'products',
            'smallImage',
            'largeImage',
        ];

        $extendOptionsManager = $this->container->get('oro_entity_extend.migration.options_manager');

        foreach ($excludeFields as $name) {
            // Works in case when the affected relation does not yet exist.
            $extendOptionsManager
                ->mergeColumnOptions($table->getName(), $name, ['importexport' => ['excluded' => true]]);

            // Works in case when the affected field already exists.
            $queries->addPostQuery(
                new UpdateEntityConfigFieldValueQuery(Category::class, $name, 'importexport', 'excluded', true)
            );
        }
    }
}
