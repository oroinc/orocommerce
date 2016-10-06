<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class OroSEOBundle implements Migration
{
    const ENTITY_TO = LocalizedFallbackValue::class;
    const RELATION_TYPE = 'manyToMany';

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
                        'extend',
                        'cascade',
                        ['all']
                    )
                );
            }

            $queries->addQuery(
                new UpdateEntityConfigFieldCascadeQuery(
                    $class,
                    self::ENTITY_TO,
                    self::RELATION_TYPE,
                    $fieldNames
                )
            );
        }

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
