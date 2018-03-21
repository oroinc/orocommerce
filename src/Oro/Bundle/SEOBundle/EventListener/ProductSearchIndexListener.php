<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductSearchIndexListener extends AbstractSEOSearchIndexListener
{
    /**
     * @param IndexEntityEvent $event
     * @param array $localizations
     */
    protected function process(IndexEntityEvent $event, array $localizations)
    {
        /** @var Product[] $products */
        $products = $event->getEntities();
        $categoryMap = $this->getCategoryMap($products, $localizations);

        foreach ($products as $product) {
            // Localized fields
            $category = &$categoryMap[$product->getId()];
            foreach ($localizations as $localization) {
                if (!empty($category)) {
                    foreach ($this->getMetaFieldsForEntity($category, $localization) as $metaField) {
                        $this->addPlaceholderToEvent($event, $product->getId(), $metaField, $localization->getId());
                    }
                }
            }
        }
    }

    /**
     * @param array $products
     * @param array $localizations
     * @return CategoryRepository
     */
    private function getCategoryMap(array $products, array $localizations)
    {
        return $this->getDoctrineHelper()
            ->getEntityRepository(Category::class)
            ->getCategoryMapByProducts($products, $localizations);
    }
}
