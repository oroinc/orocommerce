<?php

namespace Oro\Bundle\ShippingBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

interface ShippingMethodTypeInterface
{
    /**
     * @return string|int
     */
    public function getIdentifier();

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @return int
     */
    public function getSortOrder();

    /**
     * @return string
     */
    public function getOptionsConfigurationFormType();

    /**
     * @return array
     */
    public function getOptions();

    /**
     * @param ShippingContextInterface $context
     * @param array $methodOptions
     * @param array $typeOptions
     * @return null|Price
     */
    public function calculatePrice(ShippingContextInterface $context, array $methodOptions, array $typeOptions);
}
