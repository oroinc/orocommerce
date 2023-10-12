<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;

/**
 * Adds imageWebp to line items data.
 */
class WebpAwareDatagridLineItemsDataListener
{
    private AttachmentManager $attachmentManager;

    public function __construct(AttachmentManager $attachmentManager)
    {
        $this->attachmentManager = $attachmentManager;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        if (!$this->attachmentManager->isWebpEnabledIfSupported()) {
            return;
        }

        foreach ($event->getLineItems() as $lineItem) {
            $product = $lineItem->getProduct();
            if (!$product) {
                continue;
            }

            $lineItemData['imageWebp'] = $this->getImageUrl($product);
            $event->addDataForLineItem($lineItem->getEntityIdentifier(), $lineItemData);
        }
    }

    private function getImageUrl(Product $product): string
    {
        /** @var ArrayCollection<ProductImage> $image */
        $imageListingCollection = $product->getImagesByType('listing');
        $isEmptyImageFile = true;
        $image = null;

        if (!$imageListingCollection->isEmpty()) {
            $image = $imageListingCollection->first()?->getImage();
            $isEmptyImageFile = (bool)$image?->isEmptyFile();
        }

        return !$isEmptyImageFile ?
            $this->attachmentManager->getFilteredImageUrl($image, 'product_small', 'webp') : '';
    }
}
