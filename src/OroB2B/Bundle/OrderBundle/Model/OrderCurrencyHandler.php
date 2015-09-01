<?php

namespace OroB2B\Bundle\OrderBundle\Model;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

use OroB2B\Bundle\OrderBundle\Entity\Order;

class OrderCurrencyHandler
{
    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @param LocaleSettings $localeSettings
     */
    public function __construct(LocaleSettings $localeSettings)
    {
        $this->localeSettings = $localeSettings;
    }

    /**
     * @param Order $order
     */
    public function setOrderCurrency(Order $order)
    {
        if (!$order->getCurrency()) {
            $order->setCurrency($this->localeSettings->getCurrency());
        }
    }
}
