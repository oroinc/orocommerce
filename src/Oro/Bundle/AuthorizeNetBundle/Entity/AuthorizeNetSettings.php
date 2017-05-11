<?php

namespace Oro\Bundle\AuthorizeNetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

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
    const API_LOGIN_ID = 'api_login_id';
    const TRANSACTION_KEY = 'transaction_key';
    const CLIENT_KEY = 'client_key';
    const CREDIT_CARD_LABELS_KEY = 'credit_card_labels';
    const CREDIT_CARD_SHORT_LABELS_KEY = 'credit_card_short_labels';
    const CREDIT_CARD_PAYMENT_ACTION_KEY = 'credit_card_payment_action';
    const ALLOWED_CREDIT_CARD_TYPES_KEY = 'allowed_credit_card_types';
    const REQUIRE_CVV_ENTRY_KEY = 'require_cvv_entry';
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
    protected $apiLoginId;

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
     */
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
    protected $authNetTestMode = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="au_net_require_cvv_entry", type="boolean", options={"default"=true})
     */
    protected $authNetRequireCVVEntry = true;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->creditCardLabels = new ArrayCollection();
        $this->creditCardShortLabels = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag([
                self::API_LOGIN_ID => $this->getApiLoginId(),
                self::TRANSACTION_KEY => $this->getTransactionKey(),
                self::CLIENT_KEY => $this->getClientKey(),
                self::CREDIT_CARD_LABELS_KEY => $this->getCreditCardLabels(),
                self::CREDIT_CARD_SHORT_LABELS_KEY => $this->getCreditCardShortLabels(),
                self::CREDIT_CARD_PAYMENT_ACTION_KEY => $this->getCreditCardPaymentAction(),
                self::ALLOWED_CREDIT_CARD_TYPES_KEY => $this->getAllowedCreditCardTypes(),
                self::TEST_MODE_KEY => $this->getAuthNetTestMode(),
                self::REQUIRE_CVV_ENTRY_KEY => $this->getAuthNetRequireCVVEntry(),
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
    public function setAllowedCreditCardTypes(array $allowedCreditCardTypes)
    {
        $this->allowedCreditCardTypes = $allowedCreditCardTypes;
    }

    /**
     * @return bool
     */
    public function getAuthNetTestMode()
    {
        return $this->authNetTestMode;
    }

    /**
     * @param bool $testMode
     */
    public function setAuthNetTestMode($testMode)
    {
        $this->authNetTestMode = (bool)$testMode;
    }

    /**
     * Add creditCardLabel
     *
     * @param LocalizedFallbackValue $creditCardLabel
     *
     * @return AuthorizeNetSettings
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
     * @return AuthorizeNetSettings
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
     * @return AuthorizeNetSettings
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
     * @return AuthorizeNetSettings
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
    public function getApiLoginId()
    {
        return $this->apiLoginId;
    }

    /**
     * @param string $apiLoginId
     */
    public function setApiLoginId($apiLoginId)
    {
        $this->apiLoginId = $apiLoginId;
    }

    /**
     * @param boolean $requireCVVEntry
     *
     * @return AuthorizeNetSettings
     */
    public function setAuthNetRequireCVVEntry($requireCVVEntry)
    {
        $this->authNetRequireCVVEntry = (bool)$requireCVVEntry;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getAuthNetRequireCVVEntry()
    {
        return $this->authNetRequireCVVEntry;
    }
}
