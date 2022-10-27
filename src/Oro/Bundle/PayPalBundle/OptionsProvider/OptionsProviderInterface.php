<?php

namespace Oro\Bundle\PayPalBundle\OptionsProvider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

/**
 * Provides interface for payment options provider.
 */
interface OptionsProviderInterface
{
    /**
     * Gets shipping address options.
     */
    public function getShippingAddressOptions(AbstractAddress $address): AddressOptionModel;

    /**
     * Gets line item payment options.
     *
     * @param LineItemsAwareInterface $paymentEntity
     * @return LineItemOptionModel[]
     */
    public function getLineItemOptions(LineItemsAwareInterface $paymentEntity): array;
}
