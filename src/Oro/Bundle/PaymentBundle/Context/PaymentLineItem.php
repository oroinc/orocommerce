<?php

namespace Oro\Bundle\PaymentBundle\Context;

use Symfony\Component\HttpFoundation\ParameterBag;

class PaymentLineItem extends ParameterBag implements PaymentLineItemInterface
{
    const FIELD_PRICE = 'price';
    const FIELD_PRODUCT = 'product';
    const FIELD_PRODUCT_HOLDER = 'product_holder';
    const FIELD_PRODUCT_SKU = 'product_sku';
    const FIELD_ENTITY_IDENTIFIER = 'entity_id';
    const FIELD_QUANTITY = 'quantity';
    const FIELD_PRODUCT_UNIT = 'product_unit';
    const FIELD_PRODUCT_UNIT_CODE = 'product_unit_code';

    /**
     * {@inheritDoc}
     */
    public function __construct(array $parameters)
    {
        parent::__construct($parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice()
    {
        return $this->get(self::FIELD_PRICE);
    }

    /**
     * {@inheritDoc}
     */
    public function getProduct()
    {
        return $this->get(self::FIELD_PRODUCT);
    }

    /**
     * {@inheritDoc}
     */
    public function getProductHolder()
    {
        return $this->get(self::FIELD_PRODUCT_HOLDER);
    }

    /**
     * {@inheritDoc}
     */
    public function getProductSku()
    {
        return $this->get(self::FIELD_PRODUCT_SKU);
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityIdentifier()
    {
        return $this->get(self::FIELD_ENTITY_IDENTIFIER);
    }

    /**
     * {@inheritDoc}
     */
    public function getQuantity()
    {
        return $this->get(self::FIELD_QUANTITY);
    }

    /**
     * {@inheritDoc}
     */
    public function getProductUnit()
    {
        return $this->get(self::FIELD_PRODUCT_UNIT);
    }

    /**
     * {@inheritDoc}
     */
    public function getProductUnitCode()
    {
        return $this->get(self::FIELD_PRODUCT_UNIT_CODE);
    }
}
