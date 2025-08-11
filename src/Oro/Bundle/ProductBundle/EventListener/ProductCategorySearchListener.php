<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;

/**
 * Adds product category data to the search index.
 */
class ProductCategorySearchListener
{
    public function collectEntityMapEvent(SearchMappingCollectEvent $event): void
    {
        $mapConfig = $event->getMappingConfig();
        $mapConfig[Product::class]['fields'][] = [
            'name' => 'category',
            'target_type' => 'text',
            'target_fields' => ['category']
        ];
        $event->setMappingConfig($mapConfig);
    }

    public function prepareEntityMapEvent(PrepareEntityMapEvent $event): void
    {
        $data = $event->getData();
        $className = $event->getClassName();
        if ($className !== Product::class) {
            return;
        }

        /** @var $entity Product */
        $entity = $event->getEntity();
        $categoryPath = '';
        foreach ($this->getCategoryPath($entity) as $category) {
            $categoryPath .= $category->getDefaultTitle() . ' ';
        }

        $data['text']['category'] = $categoryPath;

        $event->setData($data);
    }

    public function getCategoryPath(Product $product): array
    {
        $category = $product->getCategory();

        if (!$category) {
            return [];
        }

        $path = [];

        while ($category !== null) {
            array_unshift($path, $category);
            $category = $category->getParentCategory();
        }

        return $path;
    }
}
