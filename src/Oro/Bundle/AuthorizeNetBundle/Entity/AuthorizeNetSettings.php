<?php

namespace Oro\Bundle\AuthorizeNetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\AuthorizeNetBundle\Entity\Repository\AuthorizeNetSettingsRepository")
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AuthorizeNetSettings extends Transport
{
    const API_LOGIN = 'api_login';
    const TRANSACTION_KEY = 'transaction_key';
    const CLIENT_KEY = 'client_key';
    const CREDIT_CARD_LABELS_KEY = 'credit_card_labels';
    const CREDIT_CARD_SHORT_LABELS_KEY = 'credit_card_short_labels';
    const CREDIT_CARD_PAYMENT_ACTION_KEY = 'credit_card_payment_action';
    const ALLOWED_CREDIT_CARD_TYPES_KEY = 'allowed_credit_card_types';
    const TEST_MODE_KEY = 'test_mode';

    /**
     * @var ParameterBag
     */
    protected $settings;

    /**
     * @var string
     *
     * @ORM\Column(name="au_net_api_login", type="string", length=255, nullable=false)
     */
    protected $apiLogin;

    /**
     * @var string
     *
     * @ORM\Column(name="au_net_transaction_key", type="string", length=255, nullable=false)
     */
    protected $transactionKey;

    /**
     * @var string
     *
     * @ORM\Column(name="au_net_client_key", type="string", length=255, nullable=false)
     */
    protected $clientKey;

    /**
     * @var string
     *
     * @ORM\Column(name="au_net_credit_card_action", type="string", length=255, nullable=false)
     */
    protected $creditCardPaymentAction;

    /**
     * @var array
     *
     * @ORM\Column(name="au_net_allowed_card_types", type="array", length=255, nullable=false)
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
     *      name="oro_au_net_credit_card_lbl",
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
     *      name="oro_au_net_credit_card_sh_lbl",
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
     * @var boolean
     *
     * @ORM\Column(name="au_net_test_mode", type="boolean", options={"default"=false})
     */
    protected $testMode = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->creditCardLabels = new ArrayCollection();
        $this->creditCardShortLabels = new ArrayCollection();
    }

    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag([
                self::CREDIT_CARD_LABELS_KEY => $this->getCreditCardLabels(),
                self::CREDIT_CARD_SHORT_LABELS_KEY => $this->getCreditCardShortLabels(),
                self::CREDIT_CARD_PAYMENT_ACTION_KEY => $this->getCreditCardPaymentAction(),
                self::ALLOWED_CREDIT_CARD_TYPES_KEY => $this->getAllowedCreditCardTypes(),
                self::TEST_MODE_KEY => $this->getTestMode()
            ]);
        }

        return $this->settings;
    }

    /**
     * @return string
     */
    public function getCreditCardPaymentAction()
    {
        return $this->creditCardPaymentAction;
    }

    /**
     * @param string $creditCardPaymentAction
     */
    public function setCreditCardPaymentAction($creditCardPaymentAction)
    {
        $this->creditCardPaymentAction = $creditCardPaymentAction;
    }

    /**
     * @return array
     */
    public function getAllowedCreditCardTypes()
    {
        return $this->allowedCreditCardTypes;
    }

    /**
     * @param array $allowedCreditCardTypes
     */
    public function setAllowedCreditCardTypes($allowedCreditCardTypes)
    {
        $this->allowedCreditCardTypes = $allowedCreditCardTypes;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getCreditCardLabels()
    {
        return $this->creditCardLabels;
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $creditCardLabels
     */
    public function setCreditCardLabels($creditCardLabels)
    {
        $this->creditCardLabels = $creditCardLabels;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getCreditCardShortLabels()
    {
        return $this->creditCardShortLabels;
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $creditCardShortLabels
     */
    public function setCreditCardShortLabels($creditCardShortLabels)
    {
        $this->creditCardShortLabels = $creditCardShortLabels;
    }

    /**
     * @return bool
     */
    public function getTestMode()
    {
        return $this->testMode;
    }

    /**
     * @param bool $testMode
     */
    public function setTestMode($testMode)
    {
        $this->testMode = $testMode;
    }

    /**
     * @return string
     */
    public function getTransactionKey()
    {
        return $this->transactionKey;
    }

    /**
     * @param string $transactionKey
     */
    public function setTransactionKey($transactionKey)
    {
        $this->transactionKey = $transactionKey;
    }

    /**
     * @return string
     */
    public function getClientKey()
    {
        return $this->clientKey;
    }

    /**
     * @param string $clientKey
     */
    public function setClientKey($clientKey)
    {
        $this->clientKey = $clientKey;
    }
    /**
     * @return string
     */
    public function getApiLogin()
    {
        return $this->apiLogin;
    }

    /**
     * @param string $apiLogin
     */
    public function setApiLogin($apiLogin)
    {
        $this->apiLogin = $apiLogin;
    }
}
