<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderManager;

class SummaryDataProvider implements DataProviderInterface
{
    /** @var  CheckoutDataProviderManager */
    protected $checkoutDataProviderManager;

    /**
     * @param CheckoutDataProviderManager $checkoutDataProviderManager
     */
    public function __construct(CheckoutDataProviderManager $checkoutDataProviderManager)
    {
        $this->checkoutDataProviderManager = $checkoutDataProviderManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        throw new \BadMethodCallException('Not implemented yet');
    }

    /**
     * @param ContextInterface $context
     * @return ArrayCollection
     */
    public function getData(ContextInterface $context)
    {
        /** @var Checkout $checkout */
        $checkout = $context->get('checkout');

        return new ArrayCollection($this->checkoutDataProviderManager->getData($checkout));
    }
}
