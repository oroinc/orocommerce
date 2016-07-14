<?php

namespace OroB2B\Bundle\MoneyOrderBundle\Method\View;

use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\OroB2BMoneyOrderExtension;
use OroB2B\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;

class MoneyOrderView implements PaymentMethodViewInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(array $context = [])
    {
        return [
            'pay_to'  => $this->getConfigValue(Configuration::MONEY_ORDER_PAY_TO_KEY),
            'send_to' => $this->getConfigValue(Configuration::MONEY_ORDER_SEND_TO_KEY),
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
        return (int)$this->getConfigValue(Configuration::MONEY_ORDER_SORT_ORDER_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->getConfigValue(Configuration::MONEY_ORDER_LABEL_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return $this->getConfigValue(Configuration::MONEY_ORDER_SHORT_LABEL_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethodType()
    {
        return MoneyOrder::TYPE;
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function getConfigValue($key)
    {
        $key = OroB2BMoneyOrderExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;

        return $this->configManager->get($key);
    }
}
