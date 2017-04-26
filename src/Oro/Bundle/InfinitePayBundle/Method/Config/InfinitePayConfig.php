<?php

namespace Oro\Bundle\InfinitePayBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\ParameterBag\AbstractParameterBagPaymentConfig;

class InfinitePayConfig extends AbstractParameterBagPaymentConfig implements InfinitePayConfigInterface
{
    const CLIENT_REF_KEY = 'client_ref';
    const USERNAME_KEY = 'username';
    const PASSWORD_KEY = 'password';
    const SECRET_KEY = 'secret';

    const AUTO_CAPTURE_KEY = 'auto_capture';
    const AUTO_ACTIVATE_KEY = 'auto_activate';

    const TEST_MODE_KEY = 'test_mode';
    const DEBUG_MODE_KEY = 'debug_mode';

    const INVOICE_DUE_PERIOD_KEY = 'invoice_due_period';
    const INVOICE_SHIPPING_DURATION_KEY = 'invoice_shipping_duration';


    /**
     * @return bool
     */
    public function isAutoCaptureEnabled()
    {
        return (bool) $this->get(self::AUTO_CAPTURE_KEY);
    }

    /**
     * @return bool
     */
    public function isAutoActivateEnabled()
    {
        return (bool) $this->get(self::AUTO_ACTIVATE_KEY);
    }

    /**
     * @return bool
     */
    public function isTestModeEnabled()
    {
        return (bool) $this->get(self::TEST_MODE_KEY);
    }

    /**
     * @return bool
     */
    public function isDebugModeEnabled()
    {
        return (bool) $this->get(self::DEBUG_MODE_KEY);
    }

    /**
     * @return string
     */
    public function getClientRef()
    {
        return (string) $this->get(self::CLIENT_REF_KEY);
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return (string) $this->get(self::USERNAME_KEY);
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return (string) $this->get(self::PASSWORD_KEY);
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return (string) $this->get(self::SECRET_KEY);
    }

    /**
     * @return int
     */
    public function getInvoiceDuePeriod()
    {
        return (int) $this->get(self::INVOICE_DUE_PERIOD_KEY);
    }

    /**
     * @return int
     */
    public function getShippingDuration()
    {
        return (int) $this->get(self::INVOICE_SHIPPING_DURATION_KEY);
    }
}
