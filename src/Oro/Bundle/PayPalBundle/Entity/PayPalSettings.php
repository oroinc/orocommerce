<?php

namespace Oro\Bundle\PayPalBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * PayPalSettings entity
 * @ORM\Entity(repositoryClass="Oro\Bundle\PayPalBundle\Entity\Repository\PayPalSettingsRepository")
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PayPalSettings extends Transport
{
    const CREDIT_CARD_LABELS_KEY = 'credit_card_labels';
    const CREDIT_CARD_SHORT_LABELS_KEY = 'credit_card_short_labels';
    const CREDIT_CARD_PAYMENT_ACTION_KEY = 'credit_card_payment_action';

    const EXPRESS_CHECKOUT_NAME_KEY = 'express_checkout_name';
    const EXPRESS_CHECKOUT_LABELS_KEY = 'express_checkout_labels';
    const EXPRESS_CHECKOUT_SHORT_LABELS_KEY = 'express_checkout_short_labels';
    const EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY = 'express_checkout_payment_action';

    const ALLOWED_CREDIT_CARD_TYPES_KEY = 'allowed_credit_card_types';
    const PARTNER_KEY = 'partner';
    const VENDOR_KEY = 'vendor';
    const USER_KEY = 'user';
    const PASSWORD_KEY = 'password';

    const TEST_MODE_KEY = 'test_mode';
    const DEBUG_MODE_KEY = 'debug_mode';

    const USE_PROXY_KEY = 'use_proxy';
    const PROXY_HOST_KEY = 'proxy_host';
    const PROXY_PORT_KEY = 'proxy_port';

    const ENABLE_SSL_VERIFICATION_KEY = 'enable_ssl_verification';
    const REQUIRE_CVV_ENTRY_KEY = 'require_cvv_entry';
    const ZERO_AMOUNT_AUTHORIZATION_KEY = 'zero_amount_authorization';
    const AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY = 'authorization_for_required_amount';

    /**
     * @var ParameterBag
     */
    protected $settings;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_credit_card_action", type="string", length=255, nullable=false)
     */
    protected $creditCardPaymentAction;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_express_checkout_action", type="string", length=255, nullable=false)
     */
    protected $expressCheckoutPaymentAction;

    /**
     * @var array
     *
     * @ORM\Column(name="pp_allowed_card_types", type="array", length=255, nullable=false)
     **/
    protected $allowedCreditCardTypes = [];

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_paypal_credit_card_lbl",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $creditCardLabels;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_paypal_credit_card_sh_lbl",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $creditCardShortLabels;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_paypal_xprss_chkt_lbl",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $expressCheckoutLabels;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_paypal_xprss_chkt_shrt_lbl",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $expressCheckoutShortLabels;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_express_checkout_name", type="string", length=255, nullable=false)
     */
    protected $expressCheckoutName;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_partner", type="crypted_string", length=255, nullable=false)
     */
    protected $partner;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_vendor", type="crypted_string", length=255, nullable=false)
     */
    protected $vendor;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_user", type="crypted_string", length=255, nullable=false)
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_password", type="crypted_string", length=255, nullable=false)
     */
    protected $password;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pp_test_mode", type="boolean", options={"default"=false})
     */
    protected $testMode = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pp_debug_mode", type="boolean", options={"default"=false})
     */
    protected $debugMode = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pp_require_cvv_entry", type="boolean", options={"default"=true})
     */
    protected $requireCVVEntry = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pp_zero_amount_authorization", type="boolean", options={"default"=false})
     */
    protected $zeroAmountAuthorization = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pp_auth_for_req_amount", type="boolean", options={"default"=false})
     */
    protected $authorizationForRequiredAmount = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pp_use_proxy", type="boolean", options={"default"=false})
     */
    protected $useProxy = false;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_proxy_host", type="crypted_string", length=255, nullable=false)
     */
    protected $proxyHost;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_proxy_port", type="crypted_string", length=255, nullable=false)
     */
    protected $proxyPort;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pp_enable_ssl_verification", type="boolean", options={"default"=true})
     */
    protected $enableSSLVerification = true;

    /**
     * @return ParameterBag
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    self::CREDIT_CARD_LABELS_KEY => $this->getCreditCardLabels(),
                    self::CREDIT_CARD_SHORT_LABELS_KEY => $this->getCreditCardShortLabels(),
                    self::EXPRESS_CHECKOUT_LABELS_KEY => $this->getExpressCheckoutLabels(),
                    self::EXPRESS_CHECKOUT_SHORT_LABELS_KEY => $this->getExpressCheckoutShortLabels(),
                    self::CREDIT_CARD_PAYMENT_ACTION_KEY => $this->getCreditCardPaymentAction(),
                    self::EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY => $this->getExpressCheckoutPaymentAction(),
                    self::ALLOWED_CREDIT_CARD_TYPES_KEY => $this->getAllowedCreditCardTypes(),
                    self::EXPRESS_CHECKOUT_NAME_KEY => $this->getExpressCheckoutName(),
                    self::PARTNER_KEY => $this->getPartner(),
                    self::VENDOR_KEY => $this->getVendor(),
                    self::USER_KEY => $this->getUser(),
                    self::PASSWORD_KEY => $this->getPassword(),
                    self::TEST_MODE_KEY => $this->getTestMode(),
                    self::DEBUG_MODE_KEY => $this->getDebugMode(),
                    self::REQUIRE_CVV_ENTRY_KEY => $this->getRequireCVVEntry(),
                    self::ZERO_AMOUNT_AUTHORIZATION_KEY => $this->getZeroAmountAuthorization(),
                    self::AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY => $this->getAuthorizationForRequiredAmount(),
                    self::USE_PROXY_KEY => $this->getUseProxy(),
                    self::PROXY_HOST_KEY => $this->getProxyHost(),
                    self::PROXY_PORT_KEY => $this->getProxyPort(),
                    self::ENABLE_SSL_VERIFICATION_KEY => $this->getEnableSSLVerification(),
                ]
            );
        }

        return $this->settings;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->creditCardLabels = new ArrayCollection();
        $this->creditCardShortLabels = new ArrayCollection();
        $this->expressCheckoutLabels = new ArrayCollection();
        $this->expressCheckoutShortLabels = new ArrayCollection();
    }

    /**
     * Set expressCheckoutName
     *
     * @param string $expressCheckoutName
     *
     * @return PayPalSettings
     */
    public function setExpressCheckoutName($expressCheckoutName)
    {
        $this->expressCheckoutName = $expressCheckoutName;

        return $this;
    }

    /**
     * Get expressCheckoutName
     *
     * @return string
     */
    public function getExpressCheckoutName()
    {
        return $this->expressCheckoutName;
    }

    /**
     * Set partner
     *
     * @param string $partner
     *
     * @return PayPalSettings
     */
    public function setPartner($partner)
    {
        $this->partner = $partner;

        return $this;
    }

    /**
     * Get partner
     *
     * @return string
     */
    public function getPartner()
    {
        return $this->partner;
    }

    /**
     * Set vendor
     *
     * @param string $vendor
     *
     * @return PayPalSettings
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * Get vendor
     *
     * @return string
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * Set user
     *
     * @param string $user
     *
     * @return PayPalSettings
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return PayPalSettings
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set testMode
     *
     * @param boolean $testMode
     *
     * @return PayPalSettings
     */
    public function setTestMode($testMode)
    {
        $this->testMode = (bool)$testMode;

        return $this;
    }

    /**
     * Get testMode
     *
     * @return boolean
     */
    public function getTestMode()
    {
        return $this->testMode;
    }

    /**
     * Set debugMode
     *
     * @param boolean $debugMode
     *
     * @return PayPalSettings
     */
    public function setDebugMode($debugMode)
    {
        $this->debugMode = (bool)$debugMode;

        return $this;
    }

    /**
     * Get debugMode
     *
     * @return boolean
     */
    public function getDebugMode()
    {
        return $this->debugMode;
    }

    /**
     * Set requireCVVEntry
     *
     * @param boolean $requireCVVEntry
     *
     * @return PayPalSettings
     */
    public function setRequireCVVEntry($requireCVVEntry)
    {
        $this->requireCVVEntry = (bool)$requireCVVEntry;

        return $this;
    }

    /**
     * Get requireCVVEntry
     *
     * @return boolean
     */
    public function getRequireCVVEntry()
    {
        return $this->requireCVVEntry;
    }

    /**
     * Set zeroAmountAuthorization
     *
     * @param boolean $zeroAmountAuthorization
     *
     * @return PayPalSettings
     */
    public function setZeroAmountAuthorization($zeroAmountAuthorization)
    {
        $this->zeroAmountAuthorization = (bool)$zeroAmountAuthorization;

        return $this;
    }

    /**
     * Get zeroAmountAuthorization
     *
     * @return boolean
     */
    public function getZeroAmountAuthorization()
    {
        return $this->zeroAmountAuthorization;
    }

    /**
     * Set authorizationForRequiredAmount
     *
     * @param boolean $authorizationForRequiredAmount
     *
     * @return PayPalSettings
     */
    public function setAuthorizationForRequiredAmount($authorizationForRequiredAmount)
    {
        $this->authorizationForRequiredAmount = (bool)$authorizationForRequiredAmount;

        return $this;
    }

    /**
     * Get authorizationForRequiredAmount
     *
     * @return boolean
     */
    public function getAuthorizationForRequiredAmount()
    {
        return $this->authorizationForRequiredAmount;
    }

    /**
     * Set useProxy
     *
     * @param boolean $useProxy
     *
     * @return PayPalSettings
     */
    public function setUseProxy($useProxy)
    {
        $this->useProxy = (bool)$useProxy;

        return $this;
    }

    /**
     * Get useProxy
     *
     * @return boolean
     */
    public function getUseProxy()
    {
        return $this->useProxy;
    }

    /**
     * Set proxyHost
     *
     * @param string $proxyHost
     *
     * @return PayPalSettings
     */
    public function setProxyHost($proxyHost)
    {
        $this->proxyHost = $proxyHost;

        return $this;
    }

    /**
     * Get proxyHost
     *
     * @return string
     */
    public function getProxyHost()
    {
        return $this->proxyHost;
    }

    /**
     * Set proxyPort
     *
     * @param string $proxyPort
     *
     * @return PayPalSettings
     */
    public function setProxyPort($proxyPort)
    {
        $this->proxyPort = $proxyPort;

        return $this;
    }

    /**
     * Get proxyPort
     *
     * @return string
     */
    public function getProxyPort()
    {
        return $this->proxyPort;
    }

    /**
     * Set enableSSLVerification
     *
     * @param boolean $enableSSLVerification
     *
     * @return PayPalSettings
     */
    public function setEnableSSLVerification($enableSSLVerification)
    {
        $this->enableSSLVerification = (bool)$enableSSLVerification;

        return $this;
    }

    /**
     * Get enableSSLVerification
     *
     * @return boolean
     */
    public function getEnableSSLVerification()
    {
        return $this->enableSSLVerification;
    }

    /**
     * Add creditCardLabel
     *
     * @param LocalizedFallbackValue $creditCardLabel
     *
     * @return PayPalSettings
     */
    public function addCreditCardLabel(LocalizedFallbackValue $creditCardLabel)
    {
        if (!$this->creditCardLabels->contains($creditCardLabel)) {
            $this->creditCardLabels->add($creditCardLabel);
        }

        return $this;
    }

    /**
     * Remove creditCardLabel
     *
     * @param LocalizedFallbackValue $creditCardLabel
     *
     * @return PayPalSettings
     */
    public function removeCreditCardLabel(LocalizedFallbackValue $creditCardLabel)
    {
        if ($this->creditCardLabels->contains($creditCardLabel)) {
            $this->creditCardLabels->removeElement($creditCardLabel);
        }

        return $this;
    }

    /**
     * Get creditCardLabels
     *
     * @return Collection
     */
    public function getCreditCardLabels()
    {
        return $this->creditCardLabels;
    }

    /**
     * Add creditCardShortLabel
     *
     * @param LocalizedFallbackValue $creditCardShortLabel
     *
     * @return PayPalSettings
     */
    public function addCreditCardShortLabel(LocalizedFallbackValue $creditCardShortLabel)
    {
        if (!$this->creditCardShortLabels->contains($creditCardShortLabel)) {
            $this->creditCardShortLabels->add($creditCardShortLabel);
        }

        return $this;
    }

    /**
     * Remove creditCardShortLabel
     *
     * @param LocalizedFallbackValue $creditCardShortLabel
     *
     * @return PayPalSettings
     */
    public function removeCreditCardShortLabel(LocalizedFallbackValue $creditCardShortLabel)
    {
        if ($this->creditCardShortLabels->contains($creditCardShortLabel)) {
            $this->creditCardShortLabels->removeElement($creditCardShortLabel);
        }

        return $this;
    }

    /**
     * Get creditCardShortLabels
     *
     * @return Collection
     */
    public function getCreditCardShortLabels()
    {
        return $this->creditCardShortLabels;
    }

    /**
     * Add expressCheckoutLabel
     *
     * @param LocalizedFallbackValue $expressCheckoutLabel
     *
     * @return PayPalSettings
     */
    public function addExpressCheckoutLabel(LocalizedFallbackValue $expressCheckoutLabel)
    {
        if (!$this->expressCheckoutLabels->contains($expressCheckoutLabel)) {
            $this->expressCheckoutLabels->add($expressCheckoutLabel);
        }

        return $this;
    }

    /**
     * Remove expressCheckoutLabel
     *
     * @param LocalizedFallbackValue $expressCheckoutLabel
     *
     * @return PayPalSettings
     */
    public function removeExpressCheckoutLabel(LocalizedFallbackValue $expressCheckoutLabel)
    {
        if ($this->expressCheckoutLabels->contains($expressCheckoutLabel)) {
            $this->expressCheckoutLabels->removeElement($expressCheckoutLabel);
        }

        return $this;
    }

    /**
     * Get expressCheckoutLabels
     *
     * @return Collection
     */
    public function getExpressCheckoutLabels()
    {
        return $this->expressCheckoutLabels;
    }

    /**
     * Add expressCheckoutShortLabel
     *
     * @param LocalizedFallbackValue $expressCheckoutShortLabel
     *
     * @return PayPalSettings
     */
    public function addExpressCheckoutShortLabel(LocalizedFallbackValue $expressCheckoutShortLabel)
    {
        if (!$this->expressCheckoutShortLabels->contains($expressCheckoutShortLabel)) {
            $this->expressCheckoutShortLabels->add($expressCheckoutShortLabel);
        }

        return $this;
    }

    /**
     * Remove expressCheckoutShortLabel
     *
     * @param LocalizedFallbackValue $expressCheckoutShortLabel
     *
     * @return PayPalSettings
     */
    public function removeExpressCheckoutShortLabel(LocalizedFallbackValue $expressCheckoutShortLabel)
    {
        if ($this->expressCheckoutShortLabels->contains($expressCheckoutShortLabel)) {
            $this->expressCheckoutShortLabels->removeElement($expressCheckoutShortLabel);
        }

        return $this;
    }

    /**
     * Get expressCheckoutShortLabels
     *
     * @return Collection
     */
    public function getExpressCheckoutShortLabels()
    {
        return $this->expressCheckoutShortLabels;
    }

    /**
     * Set allowedCreditCardTypes
     *
     * @param array $allowedCreditCardTypes
     *
     * @return PayPalSettings
     */
    public function setAllowedCreditCardTypes(array $allowedCreditCardTypes)
    {
        $this->allowedCreditCardTypes = $allowedCreditCardTypes;

        return $this;
    }

    /**
     * Get allowedCreditCardTypes
     *
     * @return array
     */
    public function getAllowedCreditCardTypes()
    {
        return $this->allowedCreditCardTypes;
    }

    /**
     * Set creditCardPaymentAction
     *
     * @param string $creditCardPaymentAction
     *
     * @return PayPalSettings
     */
    public function setCreditCardPaymentAction($creditCardPaymentAction)
    {
        $this->creditCardPaymentAction = $creditCardPaymentAction;

        return $this;
    }

    /**
     * Get creditCardPaymentAction
     *
     * @return string
     */
    public function getCreditCardPaymentAction()
    {
        return $this->creditCardPaymentAction;
    }

    /**
     * Set expressCheckoutPaymentAction
     *
     * @param string $expressCheckoutPaymentAction
     *
     * @return PayPalSettings
     */
    public function setExpressCheckoutPaymentAction($expressCheckoutPaymentAction)
    {
        $this->expressCheckoutPaymentAction = $expressCheckoutPaymentAction;

        return $this;
    }

    /**
     * Get expressCheckoutPaymentAction
     *
     * @return string
     */
    public function getExpressCheckoutPaymentAction()
    {
        return $this->expressCheckoutPaymentAction;
    }
}
