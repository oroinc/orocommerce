<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigQuery;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManagerAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManagerAwareTrait;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Set Orphan Removal for SEO meta fields.
 */
class UpdateMetaFieldsOrphanRemovalConfig implements Migration, ExtendOptionsManagerAwareInterface
{
    use ExtendOptionsManagerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $classNames = [
            Product::class => 'oro_product',
            Category::class => 'oro_catalog_category',
            Page::class => 'oro_cms_page',
            ContentNode::class => 'oro_web_catalog_content_node',
            Brand::class => 'oro_brand'
        ];
        $seoFieldNames = [
            'metaDescriptions',
            'metaKeywords',
            'metaTitles'
        ];
        foreach ($classNames as $className => $tableName) {
            foreach ($seoFieldNames as $fieldName) {
                // Works in case when the affected relation does not yet exist.
                $this->extendOptionsManager->mergeColumnOptions(
                    $tableName,
                    $fieldName,
                    ['extend' => ['orphanRemoval' => true]]
                );

                // Works in case when the affected field already exists.
                $queries->addQuery(
                    new UpdateEntityConfigQuery(
                        $className,
                        LocalizedFallbackValue::class,
                        RelationType::MANY_TO_MANY,
                        $fieldName,
                        'orphanRemoval',
                        true
                    )
                );
                $queries->addQuery(
                    new UpdateEntityConfigFieldValueQuery(
                        $className,
                        $fieldName,
                        'extend',
                        'orphanRemoval',
                        true
                    )
                );
            }
        }
    }
}
