<?php

namespace OroB2B\Bundle\OrderBundle\Handler;

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
            //TODO: BB-3824 Change the getting currency from system configuration
            $order->setCurrency($this->localeSettings->getCurrency());
        }
    }
}
