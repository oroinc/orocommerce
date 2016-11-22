<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\CollectSurchargeEvent;
use Oro\Bundle\PaymentBundle\EventListener\AbstractSurchargeListener;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

class PaymentShippingSurchargeListener extends AbstractSurchargeListener
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
        $entity = $event->getEntity();

        if (!$this->subtotalProvider->isSupported($entity)) {
            return;
        }

        $subtotals = $this->subtotalProvider->getSubtotal($entity);
        $amount = $this->getSubtotalAmount($subtotals);

        $model = $event->getSurchargeModel();
        $model->setShippingAmount($model->getShippingAmount() + $amount);
    }
}
