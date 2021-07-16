<?php

namespace Oro\Bundle\OrderBundle\Handler;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

class OrderCurrencyHandler
{
    /**
     * @var CurrencyProviderInterface
     */
    protected $currencyProvider;

    public function __construct(CurrencyProviderInterface $currencyProvider)
    {
        $this->currencyProvider = $currencyProvider;
    }

    public function setOrderCurrency(Order $order)
    {
        if (!$order->getCurrency()) {
            $order->setCurrency($this->currencyProvider->getDefaultCurrency());
        }
    }
}
