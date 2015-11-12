<?php

namespace OroB2B\Bundle\CatalogBundle\EventListener;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

class ProductNormalizerEventListener extends AbstractProductImportEventListener
{
    /** @var Category[] */
    protected $categories = [];

    /**
     * @param ProductNormalizerEvent $event
     */
    public function onNormalize(ProductNormalizerEvent $event)
    {
        $context = $event->getContext();
        if (isset($context['fieldName'])) {
            return;
        }

        $category = $this->getCategoryByProduct($event->getProduct());
        if (!$category) {
            return;
        }

        $data = $event->getPlainData();
        $data[self::CATEGORY_KEY] = $category->getDefaultTitle();
        $event->setPlainData($data);
    }
}
