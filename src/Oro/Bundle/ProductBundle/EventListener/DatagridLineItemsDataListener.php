<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Adds line items basic data.
 */
class DatagridLineItemsDataListener
{
    public const ID = 'id';
    public const QUANTITY = 'quantity';
    public const SKU = 'sku';
    public const UNIT = 'unit';
    public const NAME = 'name';
    public const PRODUCT_ID = 'productId';
    public const IMAGE = 'image';
    public const UNITS = 'units';
    public const IS_CONFIGURABLE = 'isConfigurable';
    public const VARIANT_ID = 'variantId';
    public const PRODUCT_CONFIGURATION = 'productConfiguration';
    public const PRECISION = 'precision';

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
            if (
                $lineItem instanceof ProductKitItemLineItemInterface
                && !$lineItem->getKitItem()?->getProducts()->contains($product)
            ) {
                // The selected product is not allowed.
                $product = null;
            }

            $lineItemData = $event->getDataForLineItem($lineItemId);
            $isEnabled = $product?->isEnabled() ?? false;

            $lineItemData[self::ID] = $lineItemId;
            $lineItemData[self::QUANTITY] = $lineItem->getQuantity();
            $lineItemData[self::SKU] = null;
            $lineItemData[self::UNIT] = $lineItem->getProductUnitCode();
            $lineItemData[self::NAME] = '';

            if (!$isEnabled) {
                $event->addDataForLineItem($lineItemId, $lineItemData);
                continue;
            }

            $unitCode = $lineItem->getProductUnitCode();
            $lineItemData[self::SKU] = $lineItem->getProductSku();
            $lineItemData[self::UNIT] = $unitCode;
            $lineItemData[self::NAME] = $this->getProductName($lineItem);

            $lineItemData[self::PRODUCT_ID] = $product->getId();
            $lineItemData[self::IMAGE] = $this->getImageUrl($product);

            $unitPrecision = $this->getProductUnitPrecision($product, $unitCode);
            if ($unitPrecision !== null) {
                $lineItemData[self::UNITS][$unitCode] = [self::PRECISION => $unitPrecision];
            }

            $lineItemData[self::IS_CONFIGURABLE] = $product->isConfigurable();

            $parentProduct = $lineItem->getParentProduct();
            if ($parentProduct) {
                $lineItemData[self::VARIANT_ID] = $lineItemData[self::PRODUCT_ID];
                $lineItemData[self::PRODUCT_ID] = $parentProduct->getId();
                $lineItemData[self::PRODUCT_CONFIGURATION] = $this->getVariantFieldsValuesForLineItem($lineItem);
            }

            $event->addDataForLineItem($lineItemId, $lineItemData);
        }
    }

    protected function getProductName(ProductLineItemInterface $lineItem): string
    {
        $product = $lineItem->getProduct();
        $parentProduct = $lineItem->getParentProduct();

        return (string)$this->localizationHelper->getLocalizedValue(
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
        /** @var ArrayCollection<ProductImage> $image */
        $imageListingCollection = $product->getImagesByType('listing');
        $isEmptyImageFile = true;
        $image = null;

        if (!$imageListingCollection->isEmpty()) {
            $image = $imageListingCollection->first()?->getImage();
            $isEmptyImageFile = (bool)$image?->isEmptyFile();
        }

        return !$isEmptyImageFile ?
            $this->attachmentManager->getFilteredImageUrl($image, 'product_small') : '';
    }
}
