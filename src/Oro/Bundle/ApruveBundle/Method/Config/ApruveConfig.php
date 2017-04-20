<?php

namespace Oro\Bundle\ApruveBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\ParameterBag\AbstractParameterBagPaymentConfig;

class ApruveConfig extends AbstractParameterBagPaymentConfig implements ApruveConfigInterface
{
    /**
     * General parameters keys.
     */
    const LABEL_KEY = 'label';
    const SHORT_LABEL_KEY = 'short_label';
    const ADMIN_LABEL_KEY = 'admin_label';
    const PAYMENT_METHOD_IDENTIFIER_KEY = 'payment_method_identifier';

    /**
     * Apruve-specific parameters keys.
     */
    const TEST_MODE_KEY  = 'test_mode';
    const API_KEY_KEY  = 'api_key';
    const MERCHANT_ID_KEY  = 'merchant_id';

    /**
     * {@inheritDoc}
     */
    public function isTestMode()
    {
        return (bool)$this->get(self::TEST_MODE_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getApiKey()
    {
        return (string)$this->get(self::API_KEY_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getMerchantId()
    {
        return (string)$this->get(self::MERCHANT_ID_KEY);
    }
}
