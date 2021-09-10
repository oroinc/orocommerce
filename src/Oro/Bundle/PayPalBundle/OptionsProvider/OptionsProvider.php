<?php

namespace Oro\Bundle\PayPalBundle\OptionsProvider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PaymentBundle\Provider\PaymentOrderShippingAddressOptionsProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

/**
 * Provides options for PayPal payment method
 */
class OptionsProvider implements OptionsProviderInterface
{
    /**
     * @var PaymentOrderShippingAddressOptionsProvider
     */
    private $orderShippingAddressOptionsProvider;

    /**
     * @var LineItemOptionsProvider
     */
    private $lineItemOptionsProvider;

    public function __construct(
        PaymentOrderShippingAddressOptionsProvider $orderShippingAddressOptionsProvider,
        LineItemOptionsProvider $lineItemOptionsProvider
    ) {
        $this->orderShippingAddressOptionsProvider = $orderShippingAddressOptionsProvider;
        $this->lineItemOptionsProvider = $lineItemOptionsProvider;
    }

    public function getShippingAddressOptions(AbstractAddress $address): AddressOptionModel
    {
        return $this->orderShippingAddressOptionsProvider->getShippingAddressOptions($address);
    }

    /**
     * @param LineItemsAwareInterface $entity
     * @return LineItemOptionModel[]
     */
    public function getLineItemOptions(LineItemsAwareInterface $entity): array
    {
        return $this->lineItemOptionsProvider->getLineItemOptions($entity);
    }
}
