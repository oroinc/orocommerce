<?php

namespace Oro\Bundle\OrderBundle\Handler;

use Oro\Bundle\CurrencyBundle\Config\CurrencyConfigInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

class OrderCurrencyHandler
{
    /**
     * @var CurrencyConfigInterface
     */
    protected $currencyConfig;

    /**
     * @param CurrencyConfigInterface $currencyConfig
     */
    public function __construct(CurrencyConfigInterface $currencyConfig)
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
