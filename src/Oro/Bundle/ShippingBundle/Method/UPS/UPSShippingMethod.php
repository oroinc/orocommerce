<?php

namespace Oro\Bundle\ShippingBundle\Method\UPS;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class UPSShippingMethodType implements ShippingMethodTypeInterface
{
    /**
     * @var string|int
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $label;

    /**
     * @param string|int $identifier
     * @param string $label
     */
    public function __construct($identifier, $label)
    {
        $this->identifier = $identifier;
        $this->label = $label;
    }

    /**
     * @return string|int
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        // TODO: Implement getSortOrder() method.
    }

    /**
     * @return string
     */
    public function getOptionsConfigurationFormType()
    {
        // TODO: Implement getOptionsConfigurationFormType() method.
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        // TODO: Implement getOptions() method.
    }

    /**
     * @param ShippingContextInterface $context
     * @param array $methodOptions
     * @param array $typeOptions
     * @return null|Price
     */
    public function calculatePrice(ShippingContextInterface $context, array $methodOptions, array $typeOptions)
    {
        // TODO: Implement calculatePrice() method.
    }
}
