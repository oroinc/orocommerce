<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config;

use Oro\Bundle\MoneyOrderBundle\DependencyInjection\OroMoneyOrderExtension;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use Oro\Bundle\PaymentBundle\Method\Config\AbstractPaymentConfig;
use Oro\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;

class MoneyOrderConfig extends AbstractPaymentConfig implements MoneyOrderConfigInterface
{
    /** {@inheritdoc} */
    protected function getPaymentExtensionAlias()
    {
        return OroMoneyOrderExtension::ALIAS;
    }

    /** {@inheritdoc} */
    public function getLabel()
    {
        return (string)$this->getConfigValue(Configuration::MONEY_ORDER_LABEL_KEY);
    }

    /** {@inheritdoc} */
    public function getShortLabel()
    {
        return (string)$this->getConfigValue(Configuration::MONEY_ORDER_SHORT_LABEL_KEY);
    }

    /** {@inheritdoc} */
    public function getAdminLabel()
    {
        return (string)$this->getLabel();
    }

    /** {@inheritdoc} */
    public function getPayTo()
    {
        return (string)$this->getConfigValue(Configuration::MONEY_ORDER_PAY_TO_KEY);
    }

    /** {@inheritdoc} */
    public function getSendTo()
    {
        return (string)$this->getConfigValue(Configuration::MONEY_ORDER_SEND_TO_KEY);
    }

    /** {@inheritdoc} */
    public function getPaymentMethodIdentifier()
    {
        return (string)MoneyOrder::TYPE;
    }
}
