<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;

class PayPalConfigToSettingsConverter
{
    /**
     * @param PayPalConfig $config
     *
     * @return mixed
     */
    public function convert(PayPalConfig $config)
    {
        $settings = new PayPalSettings();

        $this->moveCreditCardDisplayOptionsData($settings, $config);
        $this->moveIntegrationSettingsData($settings, $config);
        $this->moveAdvancedSettingsData($settings, $config);
        $this->moveConnectionSettingsData($settings, $config);
        $this->moveExpressCheckoutData($settings, $config);

        return $settings;
    }

    private function moveCreditCardDisplayOptionsData(PayPalSettings $settings, PayPalConfig $config)
    {
        $settings->addCreditCardLabel($config->getCreditCardLabel());
        $settings->addCreditCardShortLabel($config->getCreditCardShortLabel());

        if ($config->getAllowedCreditCardTypes()) {
            $settings->setAllowedCreditCardTypes($config->getAllowedCreditCardTypes());
        }
    }

    private function moveIntegrationSettingsData(PayPalSettings $settings, PayPalConfig $config)
    {
        $settings->setPartner($config->getPartner());
        $settings->setUser($config->getUser());
        $settings->setPassword($config->getPassword());
        $settings->setVendor($config->getVendor());

        $testMode = $config->getTestMode();
        if ($testMode !== null) {
            $settings->setTestMode((bool)$testMode);
        }
    }

    private function moveConnectionSettingsData(PayPalSettings $settings, PayPalConfig $config)
    {
        $useProxy = $config->getUseProxy();
        if ($useProxy !== null) {
            $settings->setUseProxy((bool)$useProxy);
        }

        $settings->setProxyHost($config->getProxyHost());
        $settings->setProxyPort($config->getProxyPort());

        $enableSsl = $config->getEnableSSLVerification();
        if ($enableSsl !== null) {
            $settings->setEnableSSLVerification((bool)$enableSsl);
        }
    }

    private function moveAdvancedSettingsData(PayPalSettings $settings, PayPalConfig $config)
    {
        $settings->setCreditCardPaymentAction($config->getCreditCardPaymentAction());

        $debugMode = $config->getDebugMode();
        if ($debugMode !== null) {
            $settings->setDebugMode((bool)$debugMode);
        }

        $requireCvv = $config->getRequireCVVEntry();
        if ($requireCvv !== null) {
            $settings->setRequireCVVEntry((bool)$requireCvv);
        }

        $zeroAmount = $config->getZeroAmountAuthorization();
        if ($zeroAmount !== null) {
            $settings->setZeroAmountAuthorization((bool)$zeroAmount);
        }

        $authRequired = $config->getAuthorizationForRequiredAmount();
        if ($authRequired !== null) {
            $settings->setAuthorizationForRequiredAmount((bool)$authRequired);
        }
    }

    private function moveExpressCheckoutData(PayPalSettings $settings, PayPalConfig $config)
    {
        $expressCheckoutLabel = $config->getExpressCheckoutLabel();

        $settings->setExpressCheckoutName($expressCheckoutLabel->getString());

        $settings->addExpressCheckoutLabel($expressCheckoutLabel);
        $settings->addExpressCheckoutShortLabel($config->getExpressCheckoutShortLabel());

        $settings->setExpressCheckoutPaymentAction($config->getExpressCheckoutPaymentAction());
    }
}
