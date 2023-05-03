<?php

namespace Oro\Bundle\ProductBundle\VirtualFields;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsSelectQueryConverter;
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
    private ConfigProvider $attributeProvider;

    public function __construct(
        VirtualFieldsSelectQueryConverter $converter,
        ManagerRegistry $doctrine,
        FieldHelper $fieldHelper,
        CacheInterface $cacheProvider,
        ConfigProvider $attributeProvider,
    ) {
        $this->converter = $converter;
        $this->doctrine = $doctrine;
        $this->fieldHelper = $fieldHelper;
        $this->cacheProvider = $cacheProvider;
        $this->attributeProvider = $attributeProvider;
    }

    /**
     * @param Product[] $products
     * @param Product   $product
     *
     * @return VirtualFieldsProductDecorator
     */
    public function createDecoratedProduct(array $products, Product $product): VirtualFieldsProductDecorator
    {
        return new VirtualFieldsProductDecorator(
            $this->converter,
            $this->doctrine,
            $this->fieldHelper,
            $this->cacheProvider,
            $this->attributeProvider,
            $products,
            $product
        );
    }
}
