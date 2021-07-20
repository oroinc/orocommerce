<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;

/**
 * Provides product images urls.
 */
class ProductImagesURLsProvider
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var AttachmentManager
     */
    protected $attachmentManager;

    /**
     * @var ProductImageHelper
     */
    protected $productImageHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AttachmentManager $attachmentManager,
        ProductImageHelper $productImageHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->attachmentManager = $attachmentManager;
        $this->productImageHelper = $productImageHelper;
    }

    /**
     * @param int $productId
     * @param array $filtersNames
     * @param string $initialImageType
     *
     * @return array
     */
    public function getFilteredImagesByProductId(
        $productId,
        array $filtersNames,
        $initialImageType = ProductImageType::TYPE_LISTING
    ) {
        if (!$filtersNames) {
            return [];
        }

        /** @var Product $product */
        $product = $this->doctrineHelper->getEntityRepositoryForClass(Product::class)->find($productId);

        if (!$product) {
            return [];
        }
        /** @var ProductImage[] $productImages */
        $productImages = $this->productImageHelper->sortImages($product->getImages()->toArray());
        $images = [];
        foreach ($productImages as $productImage) {
            if ($productImage->getImage()) {
                $images[] = $this->getFilteredImageUrls($productImage, $filtersNames, $initialImageType);
            }
        }

        return $images;
    }

    private function getFilteredImageUrls(
        ProductImage $productImage,
        array $filtersNames,
        string $initialImageType
    ): array {
        $image = [];
        foreach ($filtersNames as $filterName) {
            $image[$filterName] = $this->attachmentManager->getFilteredImageUrl($productImage->getImage(), $filterName);
        }
        $image['isInitial'] = $productImage->hasType($initialImageType);

        return $image;
    }
}
