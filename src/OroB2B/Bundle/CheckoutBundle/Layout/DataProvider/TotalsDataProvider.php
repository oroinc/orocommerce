<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Provider\CheckoutTotalsProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class TotalsDataProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var CheckoutTotalsProvider
     */
    protected $checkoutTotalsProvider;

    /**
     * @var array
     */
    protected $totals = [];

    /**
     * @param CheckoutTotalsProvider $checkoutTotalsProvider
     */
    public function __construct(CheckoutTotalsProvider $checkoutTotalsProvider)
    {
        $this->checkoutTotalsProvider = $checkoutTotalsProvider;
    }

    /**
     * @param ContextInterface $context
     * @return ArrayCollection
     */
    public function getData(ContextInterface $context)
    {
        /** @var Checkout $checkout */
        $checkout = $context->data()->get('checkout');
        if (!array_key_exists($checkout->getId(), $this->totals)) {
            $totals = $this->checkoutTotalsProvider->getTotalsArray($checkout);
            foreach ($totals[TotalProcessorProvider::SUBTOTALS] as $subtotal) {
                if ($subtotal['type'] === 'subtotal') {
                    $totals['subtotal'] = $subtotal;
                    break;
                }
            }
            $this->totals[$checkout->getId()] = $totals;
        }

        return $this->totals[$checkout->getId()];
    }
}
