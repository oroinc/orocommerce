<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\CollectSurchargeEvent;
use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

abstract class AbstractSurchargeListener
{
    /** @var SubtotalProviderInterface */
    protected $subtotalProvider;

    abstract protected function setAmount(Surcharge $model, $amount);

    public function __construct(SubtotalProviderInterface $provider)
    {
        $this->subtotalProvider = $provider;
    }

    public function onCollectSurcharge(CollectSurchargeEvent $event)
    {
        $entity = $event->getEntity();

        if (!$this->subtotalProvider->isSupported($entity)) {
            return;
        }

        $subtotals = $this->subtotalProvider->getSubtotal($entity);
        $amount = $this->getSubtotalAmount($subtotals);

        $model = $event->getSurchargeModel();
        $this->setAmount($model, $amount);
    }

    /**
     * @param Subtotal|Subtotal[] $subtotals
     * @return float
     */
    protected function getSubtotalAmount($subtotals)
    {
        if (!is_array($subtotals)) {
            $subtotals = [$subtotals];
        }

        // TODO: BB-3274 Need to check and convert currency for subtotals
        $amount = 0;
        foreach ($subtotals as $subtotal) {
            if ($subtotal->getOperation() === Subtotal::OPERATION_ADD) {
                $amount += $subtotal->getAmount();
            } elseif ($subtotal->getOperation() === Subtotal::OPERATION_SUBTRACTION) {
                $amount -= $subtotal->getAmount();
            }
        }

        return $amount;
    }
}
