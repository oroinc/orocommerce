<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\CollectSurchargeEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

class PaymentDiscountSurchargeListener extends AbstractSurchargeListener
{
    /** @var SubtotalProviderInterface */
    protected $subtotalProvider;

    /**
     * @param SubtotalProviderInterface $provider
     */
    public function __construct(SubtotalProviderInterface $provider)
    {
        $this->subtotalProvider = $provider;
    }

    /**
     * @param CollectSurchargeEvent $event
     */
    public function onCollectSurcharge(CollectSurchargeEvent $event)
    {
        // TODO: This listener should work with discounts for checkout in BB-4834
        $entity = $event->getEntity();

        if (!$this->subtotalProvider->isSupported($entity)) {
            return;
        }

        $subtotals = $this->subtotalProvider->getSubtotal($entity);
        $amount = $this->getSubtotalAmount($subtotals);

        $model = $event->getSurchargeModel();
        $model->setDiscountAmount($model->getDiscountAmount() + $amount);
    }
}
