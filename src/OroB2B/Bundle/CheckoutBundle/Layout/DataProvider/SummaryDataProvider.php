<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderManager;

class SummaryDataProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var CheckoutDataProviderManager
     */
    protected $checkoutDataProviderManager;

    /**
     * @param CheckoutDataProviderManager $checkoutDataProviderManager
     */
    public function __construct(CheckoutDataProviderManager $checkoutDataProviderManager)
    {
        $this->checkoutDataProviderManager = $checkoutDataProviderManager;
    }

    /**
     * @param ContextInterface $context
     * @return ArrayCollection
     */
    public function getData(ContextInterface $context)
    {
        /** @var Checkout $checkout */
        $checkout = $context->data()->get('checkout');

        return new ArrayCollection($this->checkoutDataProviderManager->getData($checkout));
    }
}
