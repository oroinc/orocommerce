<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;

class ProductVariantAvailabilityProvider
{
    /** DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * @return array
     */
    public function getVariantFieldsWithAvailability(Product $configurableProduct, $variantParameters = [])
    {
        $variantFields = $configurableProduct->getVariantFields();

        return $variantFields;
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * $variantParameters = [
     *     'size' => 'm',
     *     'color' => 'red',
     *     'slim_fit' => true
     * ]
     * Value is extended field id for select field and true or false for boolean field
     * @return Product[]
     */
    public function getSimpleProductsByVariantFields(Product $configurableProduct, $variantParameters = [])
    {
        /** @var ProductRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(Product::class);

        return $repository->findSimpleProductsByVariantFields($configurableProduct, $variantParameters);
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * @return Product
     * @throws InvalidArgumentException
     */
    public function getSimpleProductByVariantFields(Product $configurableProduct, $variantParameters)
    {
        $simpleProducts = $this->getSimpleProductsByVariantFields($configurableProduct, $variantParameters);

        if (count($simpleProducts) !== 1) {
            throw new InvalidArgumentException('Variant values provided don\'t match exactly one simple product');
        }

        return $simpleProducts[0];
    }
}
