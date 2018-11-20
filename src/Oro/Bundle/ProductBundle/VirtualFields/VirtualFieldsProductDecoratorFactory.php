<?php

namespace Oro\Bundle\ProductBundle\VirtualFields;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsSelectQueryConverter;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Creates decorated product
 */
class VirtualFieldsProductDecoratorFactory
{
    /**
     * @var VirtualFieldsSelectQueryConverter
     */
    private $converter;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var FieldHelper
     */
    private $fieldHelper;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @param VirtualFieldsSelectQueryConverter $converter
     * @param ManagerRegistry $doctrine
     * @param FieldHelper $fieldHelper
     * @param CacheProvider $cacheProvider
     */
    public function __construct(
        VirtualFieldsSelectQueryConverter $converter,
        ManagerRegistry $doctrine,
        FieldHelper $fieldHelper,
        CacheProvider $cacheProvider
    ) {
        $this->converter = $converter;
        $this->doctrine = $doctrine;
        $this->fieldHelper = $fieldHelper;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @param Product[] $products
     * @param Product $product
     *
     * @return VirtualFieldsProductDecorator
     */
    public function createDecoratedProduct(array $products, Product $product)
    {
        return new VirtualFieldsProductDecorator(
            $this->converter,
            $this->doctrine,
            $this->fieldHelper,
            $this->cacheProvider,
            $products,
            $product
        );
    }

    /**
     * @param ProductHolderInterface[] $productHolders
     * @param Product $product
     *
     * @return VirtualFieldsProductDecorator
     */
    public function createDecoratedProductByProductHolders(array $productHolders, Product $product)
    {
        $productHoldersProducts = array_map(
            function (ProductHolderInterface $productHolder) {
                return $productHolder->getProduct();
            },
            $productHolders
        );

        return $this->createDecoratedProduct($productHoldersProducts, $product);
    }
}
