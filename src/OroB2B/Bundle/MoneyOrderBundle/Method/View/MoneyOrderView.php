<?php

namespace OroB2B\Bundle\MoneyOrderBundle\Method\View;

use OroB2B\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use OroB2B\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use OroB2B\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use OroB2B\Bundle\PaymentBundle\Method\Config\AbstractPaymentConfig;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

class MoneyOrderView implements PaymentMethodViewInterface
{
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
     * {@inheritdoc}
     */
    public function getOptions(array $context = [])
    {
        return [
            'pay_to' => $this->config->getPayTo(),
            'send_to' => $this->config->getSendTo()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getBlock()
    {
        return '_payment_methods_money_order_widget';
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return $this->config->getOrder();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->config->getLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return $this->config->getShortLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethodType()
    {
        return MoneyOrder::TYPE;
    }
}
