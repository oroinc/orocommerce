<?php

namespace Oro\Bundle\ProductBundle\Api\Processor\Shared;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Remove all types of the new image within the existing collection,
 * simulating a replace
 */
class ProcessImageTypesCollection implements ProcessorInterface
{
    /**
     * @var DoctrineHelper $doctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ImageTypeProvider $imageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @var ProductImageHelper $productImageHelper
     */
    protected $productImageHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ImageTypeProvider $imageTypeProvider
     * @param ProductImageHelper $productImageHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ImageTypeProvider $imageTypeProvider,
        ProductImageHelper $productImageHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->imageTypeProvider = $imageTypeProvider;
        $this->productImageHelper = $productImageHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $newProductImage = $context->getResult();

        if (null === $parentProduct = $newProductImage->getProduct()) {
            return;
        }

        // For each type of the new image removes type from existing parent image collection
        /** @var ProductImageType $newType */
        foreach ($newProductImage->getTypes() as $newType) {
            $this->removeImageTypeFromParent($parentProduct, $newType->getType());
        }
    }

    /**
     * Removes existing type from parent product image collection
     *
     * @param Product $parentProduct
     * @param $newTypeName
     */
    private function removeImageTypeFromParent(Product $parentProduct, $newTypeName)
    {
        /** @var ArrayCollection $imageCollections */
        $imageCollections = $parentProduct->getImages();

        //Count max number of types for each type available
        $maxNumberByType = $this->imageTypeProvider->getMaxNumberByType();

        //Count the number of types for existing collection
        $imagesByTypeCounter = $this->productImageHelper->countImagesByType($imageCollections);

        $em = $this->doctrineHelper->getEntityManagerForClass(ProductImage::class);

        /** @var ProductImage $productImage */
        foreach ($imageCollections as $productImage) {
            $persist = false;
            /** @var ProductImageType $type */
            foreach ($productImage->getTypes() as $type) {
                $name = $type->getType();
                if ($newTypeName === $name &&
                    !is_null($max = $maxNumberByType[$name]['max']) &&
                    $imagesByTypeCounter[$name] >= $max
                ) {
                    $persist = true;
                    $productImage->removeType($type);
                    break;
                }
            }
            !$persist ?: $em->persist($productImage);
        }
    }
}
