<?php

namespace Oro\Bundle\ShippingBundle\ExpressionLanguage;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

class LineItemDecorator extends ShippingLineItem
{
    /**
     * @var LineItemDecoratorFactory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $lineItems = [];

    /**
     * @var Product|ProductDecorator
     */
    protected $product;

    /**
     * @param LineItemDecoratorFactory $factory
     * @param ShippingLineItemInterface[]|array $lineItems
     * @param ShippingLineItemInterface $lineItem
     */
    public function __construct(
        LineItemDecoratorFactory $factory,
        array $lineItems,
        ShippingLineItemInterface $lineItem
    ) {
        $this->factory = $factory;
        $this->lineItems = $lineItems;
        $this->setDimensions($lineItem->getDimensions())
            ->setProductHolder($lineItem->getProductHolder())
            ->setPrice($lineItem->getPrice())
            ->setProduct($lineItem->getProduct())
            ->setProductUnit($lineItem->getProductUnit())
            ->setQuantity($lineItem->getQuantity())
            ->setWeight($lineItem->getWeight());
    }

    /**
     * {@inheritdoc}
     */
    public function setProduct(Product $product = null)
    {
        parent::setProduct($product);

        $this->product = $this->factory->createProductDecorator($this->lineItems, $product);

        return $this;
    }

    /**
     * @return Product|ProductDecorator
     */
    public function getProduct()
    {
        return $this->product;
    }
}
