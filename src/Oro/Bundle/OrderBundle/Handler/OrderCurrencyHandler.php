<?php

namespace Oro\Bundle\OrderBundle\Handler;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Handles currency assignment for orders.
 *
 * Ensures that orders have a valid currency set by assigning the default currency from the currency provider
 * when an order lacks a currency value.
 * This handler is typically invoked during order creation or processing.
 */
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
