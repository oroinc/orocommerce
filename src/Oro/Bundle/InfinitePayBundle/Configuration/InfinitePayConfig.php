<?php

namespace Oro\Bundle\InfinitePayBundle\Configuration;

use Oro\Bundle\InfinitePayBundle\DependencyInjection\Configuration;
use Oro\Bundle\InfinitePayBundle\DependencyInjection\OroInfinitePayExtension;

/**
 * @codeCoverageIgnore
 */
class InfinitePayConfig extends AbstractPaymentConfig implements InfinitePayConfigInterface
{
    const ACTION_PURCHASE_ORDER = 'purchase';
    const ACTION_CAPTURE_ORDER = 'capture';
    const ACTION_ACTIVATE_ORDER = 'activate';

    public static $availableLegalTypes = [
        'ag' => 'AG',
        'eg' => 'eG',
        'ek' => 'EK',
        'ev' => 'e.V.',
        'freelancer' => 'Freelancer',
        'gbr' => 'GbR',
        'gmbh' => 'GmbH',
        'gmbh_ig' => 'GmbH iG',
        'gmbh_co_kg' => 'GmbH & Co. KG',
        'kg' => 'KG',
        'kgaa' => 'KgaA',
        'ltd' => 'Ltd',
        'ltd_co_kg' => 'Ltd co KG',
        'ohg' => 'OHG',
        'offtl_einrichtung' => 'öffl. Einrichtung',
        'sonst_pers_ges' => 'Sonst. KapitalGes',
        'stiftung' => 'Stiftung',
        'ug' => 'UG',
        'einzel' => 'Einzelunternehmen, Kleingewerbe, Handelsvetreter',
    ];

    /**
     * @return int
     */
    public function getOrder()
    {
        return (int) $this->getConfigValue(Configuration::INFINITEPAY_SORT_ORDER_KEY);
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return (string) $this->getConfigValue(Configuration::INFINITEPAY_LABEL_KEY);
    }

    /**
     * @return string
     */
    public function getShortLabel()
    {
        return (string) $this->getConfigValue(Configuration::INFINITEPAY_LABEL_SHORT_KEY);
    }

    /**
     * @return bool
     */
    public function getIsActive()
    {
        return (bool) $this->getConfigValue(Configuration::INFINITEPAY_ENABLED_KEY);
    }

    /**
     * @return bool
     */
    public function isAutoCaptureActive()
    {
        return (bool) $this->getConfigValue(Configuration::INFINITEPAY_AUTO_CAPTURE_KEY);
    }

    /**
     * @return bool
     */
    public function isAutoActivationActive()
    {
        return (bool) $this->getConfigValue(Configuration::INFINITEPAY_AUTO_ACTIVATE_KEY);
    }

    /**
     * @return bool
     */
    public function getDebugMode()
    {
        return (bool) $this->getConfigValue(Configuration::INFINITEPAY_API_DEBUG_MODE_KEY);
    }

    /**
     * @return string
     */
    public function getClientRef()
    {
        return (string) $this->getConfigValue(Configuration::INFINITEPAY_CLIENT_REF_KEY);
    }

    /**
     * @return string
     */
    public function getUsernameToken()
    {
        return (string) $this->getConfigValue(Configuration::INFINITEPAY_USERNAME_TOKEN_KEY);
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return (string) $this->getConfigValue(Configuration::INFINITEPAY_USERNAME_KEY);
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return (string) $this->getConfigValue(Configuration::INFINITEPAY_PASSWORD_KEY);
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return (string) $this->getConfigValue(Configuration::INFINITPAY_SECRET_KEY);
    }

    /**
     * @return int
     */
    public function getInvoiceDuePeriod()
    {
        return (int) $this->getConfigValue(Configuration::INFINITYPAY_INVOICE_DUE_PERIOD);
    }

    /**
     * @return int
     */
    public function getShippingDuration()
    {
        return (int) $this->getConfigValue(Configuration::INFINITYPAY_INVOICE_SHIPPING_DURATION);
    }

    /**
     * @return string
     */
    protected function getPaymentExtensionAlias()
    {
        return OroInfinitePayExtension::ALIAS;
    }
}
