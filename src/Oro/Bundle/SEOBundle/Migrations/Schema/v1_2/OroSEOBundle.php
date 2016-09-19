<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class OroSEOBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $fieldNames = ['metaTitles', 'metaDescriptions', 'metaKeywords'];
        $classes = [Product::class, Category::class, Page::class];

        foreach ($classes as $class) {
            foreach ($fieldNames as $fieldName) {
                $queries->addQuery(
                    new UpdateEntityConfigFieldValueQuery(
                        $class,
                        $fieldName,
                        'importexport',
                        'excluded',
                        true
                    )
                );
            }
        }
    }
}
