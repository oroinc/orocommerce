<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class CheckoutTotalsProvider
{
    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @var TotalProcessorProvider
     */
    protected $totalsProvider;

    /**
     * @var MapperInterface
     */
    protected $mapper;

    /**
     * @var CheckoutShippingMethodsProviderInterface
     */
    protected $checkoutShippingMethodsProvider;

    /**
     * @param CheckoutLineItemsManager                 $checkoutLineItemsManager
     * @param TotalProcessorProvider                   $totalsProvider
     * @param MapperInterface                          $mapper
     * @param CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider
     */
    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        TotalProcessorProvider $totalsProvider,
        MapperInterface $mapper,
        CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->totalsProvider = $totalsProvider;
        $this->mapper = $mapper;
        $this->checkoutShippingMethodsProvider = $checkoutShippingMethodsProvider;
    }

    /**
     * @param Checkout $checkout
     *
     * @return array
     */
    public function getTotalsArray(Checkout $checkout)
    {
        $checkout->setShippingCost($this->checkoutShippingMethodsProvider->getPrice($checkout));
        $data = ['lineItems' => $this->checkoutLineItemsManager->getData($checkout)];
        $order = $this->mapper->map($checkout, $data);
        $this->totalsProvider->enableRecalculation();

        return $this->totalsProvider->getTotalWithSubtotalsAsArray($order);
    }
}
