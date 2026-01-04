<?php

namespace Oro\Bundle\PayPalBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\ParameterBag\AbstractParameterBagPaymentConfig;

abstract class AbstractPayPalConfig extends AbstractParameterBagPaymentConfig implements PayPalConfigInterface
{
    public const CREDENTIALS_KEY  = 'credentials';
    public const PURCHASE_ACTION_KEY  = 'purchase_action';
    public const TEST_MODE_KEY  = 'test_mode';

    #[\Override]
    public function getCredentials()
    {
        return $this->get(self::CREDENTIALS_KEY);
    }

    #[\Override]
    public function isTestMode()
    {
        return (bool)$this->get(self::TEST_MODE_KEY);
    }

    #[\Override]
    public function getPurchaseAction()
    {
        return (string)$this->get(self::PURCHASE_ACTION_KEY);
    }
}
