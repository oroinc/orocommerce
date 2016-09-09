<?php

namespace Oro\Bundle\UPSBundle\Method\UPS;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;

class UPSShippingMethodType implements ShippingMethodTypeInterface
{
    const PRICE_OPTION = 'price';
    const TYPE_OPTION = 'type';
    const HANDLING_FEE_OPTION = 'handling_fee';

    /** @var string|int */
    protected $identifier;

    /** @var string */
    protected $label;

    /**
     * @param string|int $identifier
     * @param string $label
     */
    public function __construct($identifier, $label)
    {
        $this->setIdentifier($identifier);
        $this->setLabel($label);
    }

    /**
     * @param int|string $identifier
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param mixed $optionsConfigurationFormType
     * @return $this
     */
    public function setOptionsConfigurationFormType($optionsConfigurationFormType)
    {
        $this->optionsConfigurationFormType = $optionsConfigurationFormType;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfigurationFormType()
    {
        return UPSShippingMethodOptionsType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function calculatePrice(ShippingContextInterface $context, array $methodOptions, array $typeOptions)
    {
        $price = $typeOptions[static::PRICE_OPTION];
        switch ($typeOptions[static::TYPE_OPTION]) {
            case static::PER_ORDER_TYPE:
                break;
            case static::PER_ITEM_TYPE:
                $countItems = array_sum(array_map(function (ShippingLineItemInterface $item) {
                    return $item->getQuantity();
                }, $context->getLineItems()));
                $price = $countItems * (float)$price;
                break;
            default:
                return null;
        }

        $handlingFee = 0;
        if ($typeOptions[static::HANDLING_FEE_OPTION]) {
            $handlingFee = $typeOptions[static::HANDLING_FEE_OPTION];
        }

        return Price::create((float)$price + (float)$handlingFee, $context->getCurrency());
    }
}
