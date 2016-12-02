<?php

namespace Oro\Bundle\OrderBundle\Handler;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

class OrderCurrencyHandler
{
    /**
     * @var CurrencyProviderInterface
     */
    protected $currencyConfig;

    /**
     * @param CurrencyProviderInterface $currencyConfig
     */
    public function __construct(CurrencyProviderInterface $currencyConfig)
    {
        $this->currencyConfig = $currencyConfig;
    }

    /**
     * @param Order $order
     */
    public function setOrderCurrency(Order $order)
    {
        if (!$order->getCurrency()) {
            $order->setCurrency($this->currencyConfig->getDefaultCurrency());
        }
    }
}
