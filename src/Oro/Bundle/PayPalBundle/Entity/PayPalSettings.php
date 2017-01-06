<?php

namespace Oro\Bundle\PayPalBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @ORM\Entity
 *
 * @method AbstractEnumValue getCreditCardPaymentAction()
 * @method PayPalSettings setCreditCardPaymentAction(AbstractEnumValue $enumId)
 * @method AbstractEnumValue getExpressCheckoutPaymentAction()
 * @method PayPalSettings setExpressCheckoutPaymentAction(AbstractEnumValue $enumId)
 * @method AbstractEnumValue getAllowedCreditCardTypes()
 * @method PayPalSettings setAllowedCreditCardTypes(AbstractEnumValue $enumId)
 */
class PayPalSettings extends Transport
{
    const CARD_VISA = 'visa';
    const CARD_MASTERCARD = 'mastercard';
    const CARD_DISCOVER = 'discover';
    const CARD_AMERICAN_EXPRESS = 'american_express';

    /**
     * @var ParameterBag
     */
    protected $settings;

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
     *      name="oro_paypal_credit_card_shrt_lbl",
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
     * @ORM\Column(name="pp_partner", type="string", length=255, nullable=false)
     */
    protected $partner;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_vendor", type="string", length=255, nullable=false)
     */
    protected $vendor;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_user", type="string", length=255, nullable=false)
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_password", type="string", length=255, nullable=false)
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
     * @ORM\Column(name="pp_proxy_host", type="string", length=255, nullable=false)
     */
    protected $proxyHost;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_proxy_port", type="string", length=255, nullable=false)
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
                    'credit_card_labels' => $this->getCreditCardLabels(),
                    'credit_card_short_labels' => $this->getCreditCardShortLabels(),
                    'express_checkout_labels' => $this->getExpressCheckoutLabels(),
                    'express_checkout_short_labels' => $this->getExpressCheckoutShortLabels(),
                    'credit_card_payment_action' => $this->getCreditCardPaymentAction(),
                    'express_checkout_payment_action' => $this->getExpressCheckoutPaymentAction(),
                    'allowed_credit_card_types' => $this->getAllowedCreditCardTypes(),
                    'express_checkout_name' => $this->getExpressCheckoutName(),
                    'partner' => $this->getPartner(),
                    'vendor' => $this->getVendor(),
                    'user' => $this->getUser(),
                    'password' => $this->getPassword(),
                    'test_mode' => $this->getTestMode(),
                    'debug_mode' => $this->getDebugMode(),
                    'require_cvv_entry' => $this->getRequireCVVEntry(),
                    'zero_amount_authorization' => $this->getZeroAmountAuthorization(),
                    'authorization_for_required_amount' => $this->getAuthorizationForRequiredAmount(),
                    'use_proxy' => $this->getUseProxy(),
                    'proxy_host' => $this->getProxyHost(),
                    'proxy_prt' => $this->getProxyPort(),
                    'enable_ssl_verification' => $this->getEnableSSLVerification(),
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
        $this->testMode = $testMode;

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
        $this->debugMode = $debugMode;

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
        $this->requireCVVEntry = $requireCVVEntry;

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
        $this->zeroAmountAuthorization = $zeroAmountAuthorization;

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
        $this->authorizationForRequiredAmount = $authorizationForRequiredAmount;

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
        $this->useProxy = $useProxy;

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
        $this->enableSSLVerification = $enableSSLVerification;

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
        $this->creditCardLabels[] = $creditCardLabel;

        return $this;
    }

    /**
     * Remove creditCardLabel
     *
     * @param LocalizedFallbackValue $creditCardLabel
     */
    public function removeCreditCardLabel(LocalizedFallbackValue $creditCardLabel)
    {
        $this->creditCardLabels->removeElement($creditCardLabel);
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
        $this->creditCardShortLabels[] = $creditCardShortLabel;

        return $this;
    }

    /**
     * Remove creditCardShortLabel
     *
     * @param LocalizedFallbackValue $creditCardShortLabel
     */
    public function removeCreditCardShortLabel(LocalizedFallbackValue $creditCardShortLabel)
    {
        $this->creditCardShortLabels->removeElement($creditCardShortLabel);
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
        $this->expressCheckoutLabels[] = $expressCheckoutLabel;

        return $this;
    }

    /**
     * Remove expressCheckoutLabel
     *
     * @param LocalizedFallbackValue $expressCheckoutLabel
     */
    public function removeExpressCheckoutLabel(LocalizedFallbackValue $expressCheckoutLabel)
    {
        $this->expressCheckoutLabels->removeElement($expressCheckoutLabel);
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
        $this->expressCheckoutShortLabels[] = $expressCheckoutShortLabel;

        return $this;
    }

    /**
     * Remove expressCheckoutShortLabel
     *
     * @param LocalizedFallbackValue $expressCheckoutShortLabel
     */
    public function removeExpressCheckoutShortLabel(LocalizedFallbackValue $expressCheckoutShortLabel)
    {
        $this->expressCheckoutShortLabels->removeElement($expressCheckoutShortLabel);
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
}
