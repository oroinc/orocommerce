<?php

namespace Oro\Bundle\PaymentBundle\ExpressionLanguage;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\PaymentBundle\QueryDesigner\SelectQueryConverter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class DecoratedProductLineItemFactory
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
     * @param PaymentLineItemInterface $lineItem
     *
     * @return PaymentLineItem
     */
    public function createLineItemWithDecoratedProductByLineItem(array $lineItems, PaymentLineItemInterface $lineItem)
    {
        return new PaymentLineItem(
            [
                PaymentLineItem::FIELD_PRICE => $lineItem->getPrice(),
                PaymentLineItem::FIELD_PRODUCT_UNIT => $lineItem->getProductUnit(),
                PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => $lineItem->getProductUnitCode(),
                PaymentLineItem::FIELD_QUANTITY => $lineItem->getQuantity(),
                PaymentLineItem::FIELD_PRODUCT_HOLDER => $lineItem->getProductHolder(),
                PaymentLineItem::FIELD_PRODUCT_SKU => $lineItem->getProductSku(),
                PaymentLineItem::FIELD_PRODUCT => $this->createDecoratedProduct($lineItems, $lineItem->getProduct()),
            ]
        );
    }

    /**
     * @param array $lineItems
     * @param Product $product
     *
     * @return ProductDecorator
     */
    private function createDecoratedProduct(array $lineItems, Product $product)
    {
        return new ProductDecorator(
            $this->entityFieldProvider,
            $this->converter,
            $this->doctrine,
            $this->fieldHelper,
            array_map(
                function (PaymentLineItemInterface $lineItem) {
                    return $lineItem->getProduct();
                },
                $lineItems
            ),
            $product
        );
    }
}
