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

    public function __construct(
        ConfigurableProductProvider $configurableProductProvider,
        LocalizationHelper $localizationHelper,
        AttachmentManager $attachmentManager
    ) {
        $this->configurableProductProvider = $configurableProductProvider;
        $this->localizationHelper = $localizationHelper;
        $this->attachmentManager = $attachmentManager;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        foreach ($event->getLineItems() as $lineItem) {
            $lineItemId = $lineItem->getEntityIdentifier();
            $product = $lineItem->getProduct();
            $unitCode = $lineItem->getProductUnitCode();

            $lineItemData = [
                'id' => $lineItemId,
                'sku' => $lineItem->getProductSku(),
                'quantity' => $lineItem->getQuantity(),
                'unit' => $unitCode,
                'name' => $this->getProductName($lineItem),
            ];

            if ($product) {
                $lineItemData['productId'] = $product->getId();
                $lineItemData['image'] = $this->getImageUrl($product);

                $unitPrecision = $this->getProductUnitPrecision($product, $unitCode);
                if ($unitPrecision !== null) {
                    $lineItemData['units'][$unitCode] = ['precision' => $unitPrecision];
                }

                $lineItemData['isConfigurable'] = $product->isConfigurable();

                $parentProduct = $lineItem->getParentProduct();
                if ($parentProduct) {
                    $lineItemData['variantId'] = $lineItemData['productId'];
                    $lineItemData['productId'] = $parentProduct->getId();
                    $lineItemData['productConfiguration'] = $this->getVariantFieldsValuesForLineItem($lineItem);
                }
            }

            $event->addDataForLineItem($lineItemId, $lineItemData);
        }
    }

    protected function getProductName(ProductLineItemInterface $lineItem): string
    {
        $product = $lineItem->getProduct();
        if (!$product) {
            return '';
        }

        $parentProduct = $lineItem->getParentProduct();

        return (string) $this->localizationHelper->getLocalizedValue(
            $parentProduct ? $parentProduct->getNames() : $product->getNames()
        );
    }

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

    private function getVariantFieldsValuesForLineItem(ProductLineItemInterface $lineItem): array
    {
        $configurableProductsVariantFields = $this->configurableProductProvider
            ->getVariantFieldsValuesForLineItem($lineItem, true);

        return $configurableProductsVariantFields[$lineItem->getProduct()->getId()] ?? [];
    }

    private function getImageUrl(Product $product): string
    {
        $image = $product->getImagesByType('listing')->first();

        return $image ? $this->attachmentManager->getFilteredImageUrl($image->getImage(), 'product_small') : '';
    }
}
