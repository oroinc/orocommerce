<?php

namespace Oro\Bundle\InfinitePayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\InfinitePayBundle\Entity\Repository\InfinitePaySettingsRepository")
 */
class InfinitePaySettings extends Transport
{
    const LABELS_KEY = 'infinite_pay_labels';
    const SHORT_LABELS_KEY = 'infinite_pay_short_labels';

    const CLIENT_REF_KEY = 'client_ref';
    const USERNAME_KEY = 'username';
    const PASSWORD_KEY = 'password';
    const USERNAME_TOKEN_KEY = 'username_token'; //FIXME: where is this used??
    const SECRET_KEY = 'secret'; //FIXME: where is this used??

    const AUTO_CAPTURE_KEY = 'auto_capture';
    const AUTO_ACTIVATE_KEY = 'auto_activate';

    const API_DEBUG_MODE_KEY = 'debug_mode';

    const INVOICE_DUE_PERIOD_KEY = 'invoice_due_period';
    const INVOICE_SHIPPING_DURATION_KEY = 'invoice_shipping_duration';

    const INVOICE_DUE_PERIOD_DEFAULT = 30;
    const INVOICE_SHIPPING_DURATION_DEFAULT = 21;

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
     *      name="oro_infinitepay_lbl",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $infinitePayLabels;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_infinitepay_short_lbl",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $infinitePayShortLabels;

    /**
     * @var string
     *
     * @ORM\Column(name="ipay_client_ref", type="string", length=255, nullable=false)
     */
    protected $clientRef;

    /**
     * @var string
     *
     * @ORM\Column(name="ipay_username", type="string", length=255, nullable=false)
     */
    protected $username;


    /**
     * @var string
     *
     * @ORM\Column(name="ipay_username_token", type="string", length=255, nullable=false)
     */
    protected $usernameToken;

    /**
     * @var string
     *
     * @ORM\Column(name="ipay_password", type="string", length=255, nullable=false)
     */
    protected $password;

    /**
     * @var string
     *
     * @ORM\Column(name="ipay_secret", type="string", length=255, nullable=false)
     */
    protected $secret;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ipay_auto_capture", type="boolean", options={"default"=false})
     */
    protected $autoCapture = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ipay_auto_activate", type="boolean", options={"default"=false})
     */
    protected $autoActivate = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ipay_debug_mode", type="boolean", options={"default"=false})
     */
    protected $debugMode = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="ipay_invoice_due_period", type="smallint")
     */
    protected $invoiceDuePeriod = self::INVOICE_DUE_PERIOD_DEFAULT;

    /**
     * @var integer
     *
     * @ORM\Column(name="ipay_invoice_shipping_duration", type="smallint")
     */
    protected $invoiceShippingDuration = self::INVOICE_SHIPPING_DURATION_DEFAULT;

    public function __construct()
    {
        $this->infinitePayLabels = new ArrayCollection();
        $this->infinitePayShortLabels = new ArrayCollection();
    }

    /**
     * @inheritDoc
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    self::LABELS_KEY => $this->getInfinitePayLabels(),
                    self::SHORT_LABELS_KEY => $this->getInfinitePayShortLabels(),
                    self::CLIENT_REF_KEY => $this->getClientRef(),
                    self::USERNAME_KEY => $this->getUsername(),
                    self::PASSWORD_KEY => $this->getPassword(),
                    self::USERNAME_TOKEN_KEY => $this->getUsernameToken(),
                    self::SECRET_KEY => $this->getSecret(),
                    self::AUTO_CAPTURE_KEY => $this->isAutoCapture(),
                    self::AUTO_ACTIVATE_KEY => $this->isAutoActivate(),
                    self::API_DEBUG_MODE_KEY => $this->isDebugMode(),
                    self::INVOICE_DUE_PERIOD_KEY => $this->getInvoiceDuePeriod(),
                    self::INVOICE_SHIPPING_DURATION_KEY => $this->getInvoiceShippingDuration()
                ]
            );
        }

        return $this->settings;
    }


    /**
     * Add InfinitePayLabel
     *
     * @param LocalizedFallbackValue $infinitePayLabel
     *
     * @return InfinitePaySettings
     */
    public function addInfinitePayLabel(LocalizedFallbackValue $infinitePayLabel)
    {
        if (!$this->infinitePayLabels->contains($infinitePayLabel)) {
            $this->infinitePayLabels->add($infinitePayLabel);
        }

        return $this;
    }

    /**
     * Remove InfinitePayLabel
     *
     * @param LocalizedFallbackValue $infinitePayLabel
     *
     * @return InfinitePaySettings
     */
    public function removeInfinitePayLabel(LocalizedFallbackValue $infinitePayLabel)
    {
        if ($this->infinitePayLabels->contains($infinitePayLabel)) {
            $this->infinitePayLabels->removeElement($infinitePayLabel);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getInfinitePayLabels()
    {
        return $this->infinitePayLabels;
    }

    /**
     * Add InfinitePayShortLabel
     *
     * @param LocalizedFallbackValue $infinitePayShortLabel
     *
     * @return InfinitePaySettings
     */
    public function addInfinitePayShortLabel(LocalizedFallbackValue $infinitePayShortLabel)
    {
        if (!$this->infinitePayShortLabels->contains($infinitePayShortLabel)) {
            $this->infinitePayShortLabels->add($infinitePayShortLabel);
        }

        return $this;
    }

    /**
     * Remove InfinitePayShortLabel
     *
     * @param LocalizedFallbackValue $infinitePayShortLabel
     *
     * @return InfinitePaySettings
     */
    public function removeInfinitePayShortLabel(LocalizedFallbackValue $infinitePayShortLabel)
    {
        if ($this->infinitePayShortLabels->contains($infinitePayShortLabel)) {
            $this->infinitePayShortLabels->removeElement($infinitePayShortLabel);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getInfinitePayShortLabels()
    {
        return $this->infinitePayShortLabels;
    }


    /**
     * @return string
     */
    public function getClientRef()
    {
        return $this->clientRef;
    }

    /**
     * @param string $clientRef
     * @return InfinitePaySettings
     */
    public function setClientRef($clientRef)
    {
        $this->clientRef = $clientRef;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return InfinitePaySettings
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsernameToken()
    {
        return $this->usernameToken;
    }

    /**
     * @param string $usernameToken
     * @return InfinitePaySettings
     */
    public function setUsernameToken($usernameToken)
    {
        $this->usernameToken = $usernameToken;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return InfinitePaySettings
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     * @return InfinitePaySettings
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoCapture()
    {
        return $this->autoCapture;
    }

    /**
     * @param bool $autoCapture
     * @return InfinitePaySettings
     */
    public function setAutoCapture($autoCapture)
    {
        $this->autoCapture = $autoCapture;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoActivate()
    {
        return $this->autoActivate;
    }

    /**
     * @param bool $autoActivate
     * @return InfinitePaySettings
     */
    public function setAutoActivate($autoActivate)
    {
        $this->autoActivate = $autoActivate;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDebugMode()
    {
        return $this->debugMode;
    }

    /**
     * @param bool $debugMode
     * @return InfinitePaySettings
     */
    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode;
        return $this;
    }

    /**
     * @return int
     */
    public function getInvoiceDuePeriod()
    {
        return $this->invoiceDuePeriod;
    }

    /**
     * @param int $invoiceDuePeriod
     * @return InfinitePaySettings
     */
    public function setInvoiceDuePeriod($invoiceDuePeriod)
    {
        $this->invoiceDuePeriod = $invoiceDuePeriod;
        return $this;
    }

    /**
     * @return int
     */
    public function getInvoiceShippingDuration()
    {
        return $this->invoiceShippingDuration;
    }

    /**
     * @param int $invoiceShippingDuration
     * @return InfinitePaySettings
     */
    public function setInvoiceShippingDuration($invoiceShippingDuration)
    {
        $this->invoiceShippingDuration = $invoiceShippingDuration;
        return $this;
    }
}