<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SEOBundle\Migrations\Schema\OroSEOBundleInstaller;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Changes order of SEO fields for category export.
 */
class OrderCategoryExportSeoFields implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable(OroSEOBundleInstaller::CATEGORY_TABLE_NAME);

        $fields = [
            'metaTitles',
            'metaDescriptions',
            'metaKeywords',
        ];

        $extendOptionsManager = $this->container->get('oro_entity_extend.migration.options_manager');

        $order = 70;

        for ($i = 0, $c = count($fields); $i < $c; $i++) {
            // Works in case when the affected relation does not yet exist.
            $extendOptionsManager
                ->mergeColumnOptions($table->getName(), $fields[$i], ['importexport' => ['order' => $order + $i]]);

            // Works in case when the affected field already exists.
            $queries->addPostQuery(
                new UpdateEntityConfigFieldValueQuery(
                    Category::class,
                    $fields[$i],
                    'importexport',
                    'order',
                    $order + $i
                )
            );
        }
    }
}
