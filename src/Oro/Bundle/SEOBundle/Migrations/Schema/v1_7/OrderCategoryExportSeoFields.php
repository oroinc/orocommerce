<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManagerAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManagerAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Changes order of SEO fields for category export.
 */
class OrderCategoryExportSeoFields implements Migration, ExtendOptionsManagerAwareInterface
{
    use ExtendOptionsManagerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_catalog_category');

        $fields = [
            'metaTitles',
            'metaDescriptions',
            'metaKeywords',
        ];

        $order = 70;
        for ($i = 0, $c = \count($fields); $i < $c; $i++) {
            // Works in case when the affected relation does not yet exist.
            $this->extendOptionsManager->mergeColumnOptions(
                $table->getName(),
                $fields[$i],
                ['importexport' => ['order' => $order + $i]]
            );

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
