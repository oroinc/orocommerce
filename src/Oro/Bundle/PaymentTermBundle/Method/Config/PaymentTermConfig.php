<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\AbstractPaymentSystemConfig;
use Oro\Bundle\PaymentTermBundle\DependencyInjection\Configuration;
use Oro\Bundle\PaymentTermBundle\DependencyInjection\OroPaymentTermExtension;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;

class PaymentTermConfig extends AbstractPaymentSystemConfig implements PaymentTermConfigInterface
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

    /** {@inheritdoc} */
    public function getAdminLabel()
    {
        return (string)$this->getLabel();
    }

    /** {@inheritdoc} */
    public function getPaymentMethodIdentifier()
    {
        return (string)PaymentTerm::TYPE;
    }
}
