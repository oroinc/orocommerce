<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
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
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param TotalProcessorProvider $totalsProvider
     * @param MapperInterface $mapper
     */
    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        TotalProcessorProvider $totalsProvider,
        MapperInterface $mapper
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->totalsProvider = $totalsProvider;
        $this->mapper = $mapper;
    }

    /**
     * @param Checkout $checkout
     * @return array
     */
    public function getTotalsArray(Checkout $checkout)
    {
        $data = ['lineItems' => $this->checkoutLineItemsManager->getData($checkout)];
        $order = $this->mapper->map($checkout, $data);
        $this->totalsProvider->enableRecalculation();

        return $this->totalsProvider->getTotalWithSubtotalsAsArray($order);
    }
}
