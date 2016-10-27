<?php

namespace Oro\Bundle\ShippingBundle\ExpressionLanguage;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\QueryDesigner\SelectQueryConverter;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class LineItemDecoratorFactory
{
    /**
     * @var EntityFieldProvider
     */
    protected $entityFieldProvider;

    /**
     * @var SelectQueryConverter
     */
    protected $converter;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @param EntityFieldProvider $entityFieldProvider
     * @param SelectQueryConverter $converter
     * @param ManagerRegistry $doctrine
     * @param FieldHelper $fieldHelper
     */
    public function __construct(
        EntityFieldProvider $entityFieldProvider,
        SelectQueryConverter $converter,
        ManagerRegistry $doctrine,
        FieldHelper $fieldHelper
    ) {
        $this->entityFieldProvider = $entityFieldProvider;
        $this->converter = $converter;
        $this->doctrine = $doctrine;
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * @param array $lineItems
     * @param ShippingLineItemInterface $lineItem
     * @return LineItemDecorator
     */
    public function createOrderLineItemDecorator(array $lineItems, ShippingLineItemInterface $lineItem)
    {
        return new LineItemDecorator($this, $lineItems, $lineItem);
    }

    /**
     * @param array $lineItems
     * @param Product $product
     * @return ProductDecorator
     */
    public function createProductDecorator(array $lineItems, Product $product)
    {
        return new ProductDecorator(
            $this->entityFieldProvider,
            $this->converter,
            $this->doctrine,
            $this->fieldHelper,
            array_map(function (ShippingLineItemInterface $lineItem) {
                return $lineItem->getProduct();
            }, $lineItems),
            $product
        );
    }
}
