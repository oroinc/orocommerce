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
        $this->moveLocalizationStringsToText($queries);
        $this->updateEntityFieldsConfig($queries);
    }

    private function moveLocalizationStringsToText(QueryBag $queries)
    {
        // product meta keywords
        $queries->addPostQuery(new MoveStringToTextQuery('oro_rel_1cf73d3121a159ae6e1a29'));

        // product meta descriptions
        $queries->addPostQuery(new MoveStringToTextQuery('oro_rel_1cf73d3121a159ae2725f3'));

        // CMS page keywords
        $queries->addPostQuery(new MoveStringToTextQuery('oro_rel_b438191e21a159ae6e1a29'));

        // CMS page descriptions
        $queries->addPostQuery(new MoveStringToTextQuery('oro_rel_b438191e21a159ae2725f3'));

        // category meta keywords
        $queries->addPostQuery(new MoveStringToTextQuery('oro_rel_ff3a7b9721a159ae6e1a29'));

        // category meta descriptions
        $queries->addPostQuery(new MoveStringToTextQuery('oro_rel_ff3a7b9721a159ae2725f3'));
    }

    private function updateEntityFieldsConfig(QueryBag $queries)
    {
        $fieldNames = ['metaTitles' => 'string', 'metaDescriptions' => 'text', 'metaKeywords' => 'text'];
        $classes = [Product::class, Category::class, Page::class];

        foreach ($classes as $class) {
            foreach ($fieldNames as $fieldName => $fallbackType) {
                $queries->addQuery(
                    new UpdateEntityConfigFieldValueQuery(
                        $class,
                        $fieldName,
                        'importexport',
                        'excluded',
                        false
                    )
                );
                $queries->addQuery(
                    new UpdateEntityConfigFieldValueQuery(
                        $class,
                        $fieldName,
                        'importexport',
                        'fallback_field',
                        $fallbackType
                    )
                );
            }
        }
    }
}
