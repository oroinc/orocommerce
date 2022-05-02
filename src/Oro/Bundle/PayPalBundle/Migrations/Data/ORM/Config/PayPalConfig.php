<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PayPalBundle\DependencyInjection\Configuration;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\CreditCardTypesDataProviderInterface;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\PaymentActionsDataProviderInterface;

/**
 * The config to move PayPal settings.
 */
class PayPalConfig
{
    const PAY_WITH_PAYPAL = 'Pay with PayPal';
    const PAYPAL = 'PayPal';
    const CREDIT_CARD_LABEL = 'Credit Card';

    private ConfigManager $configManager;
    private PayPalConfigKeysProvider $keysProvider;
    private PaymentActionsDataProviderInterface $paymentActionsDataProvider;
    private CreditCardTypesDataProviderInterface $creditCardTypesDataProvider;

    public function __construct(
        PaymentActionsDataProviderInterface $paymentActionsDataProvider,
        CreditCardTypesDataProviderInterface $creditCardTypesDataProvider,
        ConfigManager $configManager,
        PayPalConfigKeysProvider $keysProvider
    ) {
        $this->paymentActionsDataProvider = $paymentActionsDataProvider;
        $this->creditCardTypesDataProvider = $creditCardTypesDataProvider;
        $this->configManager = $configManager;
        $this->keysProvider = $keysProvider;
    }

    /**
     * @return null|string
     */
    public function getPartner()
    {
        return $this->getConfigValue($this->keysProvider->getPartnerKey());
    }

    /**
     * @return null|string
     */
    public function getVendor()
    {
        return $this->getConfigValue($this->keysProvider->getVendorKey());
    }

    /**
     * @return null|string
     */
    public function getUser()
    {
        return $this->getConfigValue($this->keysProvider->getUserKey());
    }

    /**
     * @return null|string
     */
    public function getPassword()
    {
        return $this->getConfigValue($this->keysProvider->getPasswordKey());
    }

    /**
     * @return null|int
     */
    public function getTestMode()
    {
        return $this->getConfigValue($this->keysProvider->getTestModeKey());
    }

    /**
     * @return null|int
     */
    public function getDebugMode()
    {
        return $this->getConfigValue($this->keysProvider->getDebugModeKey());
    }

    /**
     * @return null|int
     */
    public function getRequireCVVEntry()
    {
        return $this->getConfigValue($this->keysProvider->getRequireCVVEntryKey());
    }

    /**
     * @return null|int
     */
    public function getZeroAmountAuthorization()
    {
        return $this->getConfigValue($this->keysProvider->getZeroAmountAuthorizationKey());
    }

    /**
     * @return null|int
     */
    public function getAuthorizationForRequiredAmount()
    {
        return $this->getConfigValue($this->keysProvider->getAuthorizationForRequiredAmountKey());
    }

    /**
     * @return null|int
     */
    public function getUseProxy()
    {
        return $this->getConfigValue($this->keysProvider->getUseProxyKey());
    }

    /**
     * @return null|string
     */
    public function getProxyHost()
    {
        return $this->getConfigValue($this->keysProvider->getProxyHostKey());
    }

    /**
     * @return null|string
     */
    public function getProxyPort()
    {
        return $this->getConfigValue($this->keysProvider->getProxyPortKey());
    }

    /**
     * @return null|int
     */
    public function getEnableSSLVerification()
    {
        return $this->getConfigValue($this->keysProvider->getEnableSSLVerificationKey());
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getCreditCardLabel()
    {
        return $this->getLocalizedFallbackValueFromConfig(
            $this->keysProvider->getCreditCardLabelKey(),
            self::CREDIT_CARD_LABEL
        );
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getCreditCardShortLabel()
    {
        return $this->getLocalizedFallbackValueFromConfig(
            $this->keysProvider->getCreditCardShortLabelKey(),
            self::CREDIT_CARD_LABEL
        );
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getExpressCheckoutLabel()
    {
        return $this->getLocalizedFallbackValueFromConfig(
            $this->keysProvider->getExpressCheckoutLabelKey(),
            self::PAY_WITH_PAYPAL
        );
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getExpressCheckoutShortLabel()
    {
        return $this->getLocalizedFallbackValueFromConfig(
            $this->keysProvider->getExpressCheckoutShortLabelKey(),
            self::PAYPAL
        );
    }

    /**
     * @return string[]
     */
    public function getAllowedCreditCardTypes()
    {
        $configTypes = $this->getConfigValue($this->keysProvider->getAllowedCreditCardTypesKey()) ?: [];

        $actualTypes = $this->creditCardTypesDataProvider->getCardTypes();

        $configTypes = array_filter($configTypes, function ($configType) use ($actualTypes) {
            return in_array($configType, $actualTypes, true);
        });

        if (count($configTypes) > 0) {
            return $configTypes;
        }

        // return default card types action
        return array_splice($actualTypes, 0, 2);
    }

    /**
     * @return string
     */
    public function getCreditCardPaymentAction()
    {
        $action = $this->getConfigValue($this->keysProvider->getCreditCardPaymentActionKey());

        return $this->getPaymentActionByConfigAction($action);
    }

    /**
     * @return string
     */
    public function getExpressCheckoutPaymentAction()
    {
        $action = $this->getConfigValue($this->keysProvider->getExpressCheckoutPaymentActionKey());

        return $this->getPaymentActionByConfigAction($action);
    }

    /**
     * @return bool
     */
    public function isAllRequiredFieldsSet()
    {
        $fields = [
            $this->getCreditCardLabel(),
            $this->getCreditCardShortLabel(),
            $this->getAllowedCreditCardTypes(),
            $this->getUser(),
            $this->getPassword(),
            $this->getCreditCardPaymentAction(),
            $this->getExpressCheckoutLabel(),
            $this->getExpressCheckoutShortLabel(),
            $this->getExpressCheckoutPaymentAction(),
        ];

        foreach ($fields as $field) {
            if ($field === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    private function getConfigValue($key)
    {
        return $this->configManager->get(Configuration::getConfigKey($key));
    }

    /**
     * @param string $key
     * @param string $default
     *
     * @return LocalizedFallbackValue
     */
    private function getLocalizedFallbackValueFromConfig($key, $default)
    {
        $creditCardLabel = $this->getConfigValue($key);

        return (new LocalizedFallbackValue())->setString($creditCardLabel ?: $default);
    }

    /**
     * @param string $configAction
     *
     * @return string
     */
    private function getPaymentActionByConfigAction($configAction)
    {
        $actions = $this->paymentActionsDataProvider->getPaymentActions();

        if (in_array($configAction, $actions, true)) {
            return $configAction;
        }

        // return default payment action
        return reset($actions);
    }
}
