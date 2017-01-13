<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\View;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;

class MoneyOrderMethodViewProvider implements PaymentMethodViewProviderInterface
{
    /**
     * @var  PaymentMethodViewInterface[]
     */
    protected $methodViews;

    /**
     * @var MoneyOrderConfigInterface
     */
    protected $config;

    /**
     * @param MoneyOrderConfigInterface $config
     */
    public function __construct(MoneyOrderConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $identifiers
     * @return PaymentMethodViewInterface[]
     */
    public function getPaymentMethodViews($identifiers)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        $matchedViews = [];
        foreach ($identifiers as $paymentMethod) {
            if ($this->hasPaymentMethodView($paymentMethod)) {
                $matchedViews[$paymentMethod] = $this->getPaymentMethodView($paymentMethod);
            }
        }
        return $matchedViews;
    }

    /**
     * @param string $identifier
     * @return PaymentMethodViewInterface
     */
    public function getPaymentMethodView($identifier)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        if ($this->hasPaymentMethodView($identifier)) {
            return $this->methodViews[$identifier];
        }
        return null;
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentMethodView($identifier)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        return array_key_exists($identifier, $this->methodViews);
    }
    
    protected function collectPaymentMethodViews()
    {
        $methodView = new MoneyOrderView($this->config);
        $this->methodViews = [MoneyOrder::TYPE => $methodView];
    }
}
