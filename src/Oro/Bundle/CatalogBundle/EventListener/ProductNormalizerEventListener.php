<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

class ProductNormalizerEventListener extends AbstractProductImportEventListener
{
    /**
     * @param ProductNormalizerEvent $event
     */
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
