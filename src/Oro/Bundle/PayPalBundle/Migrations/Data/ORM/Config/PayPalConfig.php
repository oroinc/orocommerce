<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Data\ORM\Config;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;
use Oro\Bundle\PayPalBundle\Entity\CreditCardPaymentAction;
use Oro\Bundle\PayPalBundle\Entity\CreditCardType;
use Oro\Bundle\PayPalBundle\Entity\ExpressCheckoutPaymentAction;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class PayPalConfig
{
    const CARD_VISA = 'visa';
    const CARD_MASTERCARD = 'mastercard';

    const PAY_WITH_PAYPAL = 'Pay with PayPal';
    const PAYPAL = 'PayPal';
    const CREDIT_CARD_LABEL = 'Credit Card';

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var PayPalConfigKeysProvider
     */
    private $keysProvider;

    /**
     * @var CreditCardType[]
     */
    private static $creditCardTypes;

    /**
     * @var CreditCardPaymentAction[]
     */
    private static $creditCardPaymentActions;

    /**
     * @var ExpressCheckoutPaymentAction[]
     */
    private static $expressCheckoutPaymentActions;

    /**
     * @param ManagerRegistry          $managerRegistry
     * @param ConfigManager            $configManager
     * @param PayPalConfigKeysProvider $keysProvider
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ConfigManager $configManager,
        PayPalConfigKeysProvider $keysProvider
    ) {
        $this->managerRegistry = $managerRegistry;
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
     * @return ArrayCollection|CreditCardType[]
     */
    public function getAllowedCreditCardTypes()
    {
        $creditCardTypes = $this->getConfigValue($this->keysProvider->getAllowedCreditCardTypesKey());

        if ($creditCardTypes === null) {
            $creditCardTypes = [
                self::CARD_VISA,
                self::CARD_MASTERCARD,
            ];
        }
        $creditCardTypeEntities = array_filter(array_map(function ($creditCardType) {
            return $this->getCreditCardTypeEntityByConfigType($creditCardType);
        }, $creditCardTypes));

        if (empty($creditCardTypeEntities)) {
            return null;
        }

        return new ArrayCollection($creditCardTypeEntities);
    }

    /**
     * @return null|CreditCardPaymentAction
     */
    public function getCreditCardPaymentAction()
    {
        $action = $this->getConfigValue($this->keysProvider->getCreditCardPaymentActionKey());
        return $this->getCreditCardPaymentActionEntityByConfigAction($action);
    }

    /**
     * @return null|ExpressCheckoutPaymentAction
     */
    public function getExpressCheckoutPaymentAction()
    {
        $action = $this->getConfigValue($this->keysProvider->getExpressCheckoutPaymentActionKey());
        return $this->getExpressCheckoutPaymentActionEntityByConfigAction($action);
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
        return $this->configManager->get($this->getFullConfigKey($key));
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getFullConfigKey($key)
    {
        return OroPayPalExtension::ALIAS.ConfigManager::SECTION_MODEL_SEPARATOR.$key;
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
     * @param string $actionLabel
     *
     * @return null|ExpressCheckoutPaymentAction
     */
    private function getExpressCheckoutPaymentActionEntityByConfigAction($actionLabel)
    {
        if (!self::$expressCheckoutPaymentActions) {
            self::$expressCheckoutPaymentActions = $this
                ->findAllEntitiesByClassName(ExpressCheckoutPaymentAction::class);
        }

        foreach (self::$expressCheckoutPaymentActions as $actionEntity) {
            if ($actionEntity->getLabel() === $actionLabel) {
                return $actionEntity;
            }
        }

        // return default payment action
        return reset(self::$expressCheckoutPaymentActions);
    }

    /**
     * @param string $creditCardType
     *
     * @return null|CreditCardType
     */
    private function getCreditCardTypeEntityByConfigType($creditCardType)
    {
        if (!self::$creditCardTypes) {
            self::$creditCardTypes = $this->findAllEntitiesByClassName(CreditCardType::class);
        }

        foreach (self::$creditCardTypes as $typeEntity) {
            if ($typeEntity->getLabel() === $creditCardType) {
                return $typeEntity;
            }
        }

        return null;
    }

    /**
     * @param string $actionLabel
     *
     * @return null|CreditCardPaymentAction
     */
    private function getCreditCardPaymentActionEntityByConfigAction($actionLabel)
    {
        if (!self::$creditCardPaymentActions) {
            self::$creditCardPaymentActions = $this->findAllEntitiesByClassName(CreditCardPaymentAction::class);
        }

        foreach (self::$creditCardPaymentActions as $actionEntity) {
            if ($actionEntity->getLabel() === $actionLabel) {
                return $actionEntity;
            }
        }

        // return default payment action
        return reset(self::$creditCardPaymentActions);
    }

    /**
     * @param string $className
     *
     * @return array
     */
    private function findAllEntitiesByClassName($className)
    {
        return $this->managerRegistry->getRepository($className)->findAll();
    }
}
