<?php

namespace Oro\Bundle\ProductBundle\VirtualFields;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsSelectQueryConverter;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Creates decorated product
 */
class VirtualFieldsProductDecoratorFactory
{
    private VirtualFieldsSelectQueryConverter $converter;
    private ManagerRegistry $doctrine;
    private FieldHelper $fieldHelper;
    private CacheInterface $cacheProvider;

    public function __construct(
        VirtualFieldsSelectQueryConverter $converter,
        ManagerRegistry $doctrine,
        FieldHelper $fieldHelper,
        CacheInterface $cacheProvider
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
}
