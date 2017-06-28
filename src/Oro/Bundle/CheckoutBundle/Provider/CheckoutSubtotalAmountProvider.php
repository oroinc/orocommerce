<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry;

class CheckoutSubtotalAmountProvider
{
    const SUBTOTAL_PROVIDER_TYPE = 'subtotal';

    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @var MapperInterface
     */
    protected $mapper;

    /**
     * @var SubtotalProviderRegistry
     */
    protected $subtotalProviderRegistry;

    /**
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param MapperInterface $mapper
     * @param SubtotalProviderRegistry $subtotalProviderRegistry
     */
    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        MapperInterface $mapper,
        SubtotalProviderRegistry $subtotalProviderRegistry
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->mapper = $mapper;
        $this->subtotalProviderRegistry = $subtotalProviderRegistry;
    }

    /**
     * @param Checkout $checkout
     *
     * @return float
     */
    public function getSubtotalAmount(Checkout $checkout)
    {
        $data = ['lineItems' => $this->checkoutLineItemsManager->getData($checkout)];
        $order = $this->mapper->map($checkout, $data);

        foreach ($this->subtotalProviderRegistry->getSupportedProviders($order) as $provider) {
            if ($provider->getType() !== self::SUBTOTAL_PROVIDER_TYPE) {
                continue;
            }

            $subtotal = $provider->getSubtotal($order);
            if ($subtotal instanceof Subtotal) {
                return $subtotal->getAmount();
            }
        }

        return 0.0;
    }
}
