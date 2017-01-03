<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config;

use Oro\Bundle\PaymentTermBundle\DependencyInjection\Configuration;
use Oro\Bundle\PaymentTermBundle\DependencyInjection\OroPaymentTermExtension;
use Oro\Bundle\PaymentBundle\Method\Config\AbstractPaymentConfig;

class PaymentTermConfig extends AbstractPaymentConfig implements PaymentTermConfigInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getPaymentExtensionAlias()
    {
        return OroPaymentTermExtension::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return (string)$this->getConfigValue(Configuration::PAYMENT_TERM_LABEL_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return (string)$this->getConfigValue(Configuration::PAYMENT_TERM_SHORT_LABEL_KEY);
    }
}
