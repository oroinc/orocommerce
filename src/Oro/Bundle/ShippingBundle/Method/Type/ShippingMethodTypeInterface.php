<?php

namespace Oro\Bundle\ShippingBundle\Method\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;

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
     * @param ShippingContextAwareInterface $context
     * @param array $options
     * @return null|Price
     */
    public function calculatePrice(ShippingContextAwareInterface $context, array $options);
}
