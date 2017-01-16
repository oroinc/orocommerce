<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use Oro\Bundle\PaymentBundle\Method\Config\AbstractPaymentConfig;

class MoneyOrderConfig extends AbstractPaymentConfig implements MoneyOrderConfigInterface
{
    /**
     * @param Channel $channel
     */
    public function __construct(Channel $channel)
    {
        parent::__construct($channel);
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
        return (string)MoneyOrder::TYPE . '_' . $this->channel->getId();
    }
}
