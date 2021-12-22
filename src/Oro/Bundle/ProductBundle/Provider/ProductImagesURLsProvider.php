<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;

/**
 * Provides product images urls.
 */
class ProductImagesURLsProvider
{
    protected ManagerRegistry $managerRegistry;

    protected AttachmentManager $attachmentManager;

    protected ProductImageHelper $productImageHelper;

    public function __construct(
        ManagerRegistry $managerRegistry,
        AttachmentManager $attachmentManager,
        ProductImageHelper $productImageHelper
    ) {
        $this->managerRegistry = $managerRegistry;
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
        int $productId,
        array $filtersNames,
        string $initialImageType = ProductImageType::TYPE_LISTING
    ): array {
        if (!$filtersNames) {
            return [];
        }

        $product = $this->managerRegistry->getRepository(Product::class)->find($productId);
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

    protected function getFilteredImageUrls(
        ProductImage $productImage,
        array $filtersNames,
        string $initialImageType
    ): array {
        $image = [];
        foreach ($filtersNames as $filterName) {
            $file = $productImage->getImage();
            $image[$filterName][] = [
                'srcset' => $this->attachmentManager->getFilteredImageUrl($file, $filterName),
                'type' => $file->getMimeType(),
            ];
        }
        $image['isInitial'] = $productImage->hasType($initialImageType);

        return $image;
    }
}
