<?php

namespace Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\PaymentLineItemBuilderInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;

class BasicPaymentLineItemBuilder implements PaymentLineItemBuilderInterface
{
    /**
     * @var Price
     */
    private $price;

    /**
     * @var ProductUnit
     */
    private $unit;

    /**
     * @var string
     */
    private $unitCode;

    /**
     * @var int
     */
    private $quantity;

    /**
     * @var ProductHolderInterface
     */
    private $productHolder;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var string
     */
    private $productSku;

    /**
     * @param ProductUnit $unit
     * @param string $unitCode
     * @param int $quantity
     * @param ProductHolderInterface $productHolder
     */
    public function __construct(
        ProductUnit $unit,
        $unitCode,
        $quantity,
        ProductHolderInterface $productHolder
    ) {
        $this->unit = $unit;
        $this->unitCode = $unitCode;
        $this->quantity = $quantity;
        $this->productHolder = $productHolder;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult()
    {
        $params = [
            PaymentLineItem::FIELD_PRODUCT_UNIT => $this->unit,
            PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => $this->unitCode,
            PaymentLineItem::FIELD_QUANTITY => $this->quantity,
            PaymentLineItem::FIELD_PRODUCT_HOLDER => $this->productHolder,
            PaymentLineItem::FIELD_ENTITY_IDENTIFIER => $this->productHolder->getEntityIdentifier(),
        ];

        if (null !== $this->product) {
            $params[PaymentLineItem::FIELD_PRODUCT] = $this->product;
        }

        if (null !== $this->productSku) {
            $params[PaymentLineItem::FIELD_PRODUCT_SKU] = $this->productSku;
        }

        if (null !== $this->price) {
            $params[PaymentLineItem::FIELD_PRICE] = $this->price;
        }

        return new PaymentLineItem($params);
    }

    /**
     * {@inheritDoc}
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setProductSku($sku)
    {
        $this->productSku = $sku;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPrice(Price $price)
    {
        $this->price = $price;

        return $this;
    }
}
