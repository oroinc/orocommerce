<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

/**
 * Handles product normalization events during import/export operations.
 *
 * Listens to product normalization events and adds category information to the normalized
 * product data, ensuring that category relationships are preserved during import/export.
 */
class ProductNormalizerEventListener extends AbstractProductImportEventListener
{
    public function onNormalize(ProductNormalizerEvent $event)
    {
        $context = $event->getContext();
        if (array_key_exists('fieldName', $context)) {
            // It's a related Product entity (like variantLinks)
            return;
        }

        $category = $this->getCategoryByProduct($event->getProduct(), true);
        if (!$category) {
            return;
        }

        $data = $event->getPlainData();
        $data[self::CATEGORY_KEY] = $category->getDefaultTitle();
        $event->setPlainData($data);
    }
}
