<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

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
     * @param DoctrineHelper    $doctrineHelper
     * @param AttachmentManager $attachmentManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, AttachmentManager $attachmentManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * @param int   $productId
     * @param array $filtersNames
     *
     * @return array
     */
    public function getFilteredImagesByProductId($productId, array $filtersNames)
    {
        if (!$filtersNames) {
            return [];
        }

        $images = [];
        foreach ($this->getImageFiles($productId) as $imageFile) {
            $images[] = $this->getFilteredImageUrls($imageFile, $filtersNames);
        }

        return $images;
    }

    /**
     * @param int $productId
     *
     * @return array|\Oro\Bundle\AttachmentBundle\Entity\File[]
     */
    private function getImageFiles($productId)
    {
        /** @var ProductRepository $productRepo */
        $productRepo = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);

        return $productRepo->getImagesFilesByProductId($productId);
    }

    /**
     * @param File  $imageFile
     * @param array $filtersNames
     *
     * @return array
     */
    private function getFilteredImageUrls(File $imageFile, array $filtersNames)
    {
        $image = [];
        foreach ($filtersNames as $filterName) {
            $image[$filterName] = $this->attachmentManager->getFilteredImageUrl($imageFile, $filterName);
        }

        return $image;
    }
}
