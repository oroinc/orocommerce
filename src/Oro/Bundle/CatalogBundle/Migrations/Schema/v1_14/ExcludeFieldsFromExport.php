<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Migrations\Schema\OroCatalogBundleInstaller;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
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

        $options = ['importexport' => ['excluded' => true]];

        // Works in case when the affected column does not yet exist.
        $table->changeColumn('materialized_path', [OroOptions::KEY => $options]);

        $extendOptionsManager = $this->container->get('oro_entity_extend.migration.options_manager');

        // Works in case when the affected relation does not yet exist.
        foreach (['smallImage', 'largeImage'] as $relationName) {
            $extendOptionsManager->mergeColumnOptions($table->getName(), $relationName, $options);
        }

        $excludeFields = [
            'materializedPath',
            'left',
            'right',
            'level',
            'root',
            'products',
            'createdAt',
            'updatedAt',
            'smallImage',
            'largeImage',
        ];

        // Works in case when the affected field already exists.
        foreach ($excludeFields as $fieldName) {
            $queries->addPostQuery(
                new UpdateEntityConfigFieldValueQuery(Category::class, $fieldName, 'importexport', 'excluded', true)
            );
        }
    }
}
