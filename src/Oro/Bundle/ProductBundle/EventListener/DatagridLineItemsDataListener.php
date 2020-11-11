<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Adds line items basic data.
 */
class DatagridLineItemsDataListener
{
    /** @var ConfigurableProductProvider */
    private $configurableProductProvider;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var AttachmentManager */
    private $attachmentManager;

    /**
     * @param ConfigurableProductProvider $configurableProductProvider
     * @param LocalizationHelper $localizationHelper
     * @param AttachmentManager $attachmentManager
     */
    public function __construct(
        ConfigurableProductProvider $configurableProductProvider,
        LocalizationHelper $localizationHelper,
        AttachmentManager $attachmentManager
    ) {
        $this->configurableProductProvider = $configurableProductProvider;
        $this->localizationHelper = $localizationHelper;
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * @param DatagridLineItemsDataEvent $event
     */
    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        foreach ($event->getLineItems() as $lineItem) {
            $lineItemId = $lineItem->getEntityIdentifier();
            $product = $lineItem->getProduct();
            $parentProduct = $lineItem->getParentProduct();
            $productId = $product->getId();
            $unitCode = $lineItem->getProductUnitCode();
            $lineItemData = [
                'id' => $lineItemId,
                'productId' => $productId,
                'sku' => $product->getSku(),
                'image' => $this->getImageUrl($product),
                'quantity' => $lineItem->getQuantity(),
                'unit' => $unitCode,
            ];

            $unitPrecision = $this->getProductUnitPrecision($product, $unitCode);
            if ($unitPrecision !== null) {
                $lineItemData['units'][$unitCode] = ['precision' => $unitPrecision];
            }

            if ($parentProduct) {
                $namesCollection = $parentProduct->getNames();
                $lineItemData['productId'] = $parentProduct->getId();
                $lineItemData['variantId'] = $productId;
                $lineItemData['productConfiguration'] = $this->getVariantFieldsValuesForLineItem($lineItem);
            } else {
                $namesCollection = $product->getNames();
            }

            $lineItemData['name'] = (string)$this->localizationHelper->getLocalizedValue($namesCollection);

            $event->addDataForLineItem($lineItemId, $lineItemData);
        }
    }

    /**
     * @param Product $product
     * @param string $productUnitCode
     * @return int|null
     */
    private function getProductUnitPrecision(Product $product, string $productUnitCode): ?int
    {
        foreach ($product->getUnitPrecisions() as $unitPrecision) {
            if ($productUnitCode === $unitPrecision->getUnit()->getCode()) {
                if ($unitPrecision->isSell()) {
                    return $unitPrecision->getPrecision();
                }
                break;
            }
        }

        return null;
    }

    /**
     * @param ProductLineItemInterface $lineItem
     * @return array
     */
    private function getVariantFieldsValuesForLineItem(ProductLineItemInterface $lineItem): array
    {
        $configurableProductsVariantFields = $this->configurableProductProvider
            ->getVariantFieldsValuesForLineItem($lineItem, true);

        return $configurableProductsVariantFields[$lineItem->getProduct()->getId()] ?? [];
    }

    /**
     * @param Product $product
     * @return string
     */
    private function getImageUrl(Product $product): string
    {
        $image = $product->getImagesByType('listing')->first();

        return $image ? $this->attachmentManager->getFilteredImageUrl($image->getImage(), 'product_small') : '';
    }
}
