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
    const SECRET_KEY = 'secret';

    const AUTO_CAPTURE_KEY = 'auto_capture';
    const AUTO_ACTIVATE_KEY = 'auto_activate';

    const TEST_MODE_KEY = 'test_mode';
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
    protected $infinitePayClientRef;

    /**
     * @var string
     *
     * @ORM\Column(name="ipay_username", type="string", length=255, nullable=false)
     */
    protected $infinitePayUsername;

    /**
     * @var string
     *
     * @ORM\Column(name="ipay_password", type="string", length=255, nullable=false)
     */
    protected $infinitePayPassword;

    /**
     * @var string
     *
     * @ORM\Column(name="ipay_secret", type="string", length=255, nullable=false)
     */
    protected $infinitePaySecret;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ipay_auto_capture", type="boolean", options={"default"=false})
     */
    protected $infinitePayAutoCapture = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ipay_auto_activate", type="boolean", options={"default"=false})
     */
    protected $infinitePayAutoActivate = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ipay_test_mode", type="boolean", options={"default"=false})
     */
    protected $infinitePayTestMode = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ipay_debug_mode", type="boolean", options={"default"=false})
     */
    protected $infinitePayDebugMode = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="ipay_invoice_due_period", type="smallint")
     */
    protected $infinitePayInvoiceDuePeriod = self::INVOICE_DUE_PERIOD_DEFAULT;

    /**
     * @var integer
     *
     * @ORM\Column(name="ipay_invoice_shipping_duration", type="smallint")
     */
    protected $infinitePayInvoiceShippingDuration = self::INVOICE_SHIPPING_DURATION_DEFAULT;

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
                    self::CLIENT_REF_KEY => $this->getInfinitePayClientRef(),
                    self::USERNAME_KEY => $this->getInfinitePayUsername(),
                    self::PASSWORD_KEY => $this->getInfinitePayPassword(),
                    self::SECRET_KEY => $this->getInfinitePaySecret(),
                    self::AUTO_CAPTURE_KEY => $this->isInfinitePayAutoCapture(),
                    self::AUTO_ACTIVATE_KEY => $this->isInfinitePayAutoActivate(),
                    self::API_DEBUG_MODE_KEY => $this->isInfinitePayDebugMode(),
                    self::INVOICE_DUE_PERIOD_KEY => $this->getInfinitePayInvoiceDuePeriod(),
                    self::INVOICE_SHIPPING_DURATION_KEY => $this->getInfinitePayInvoiceShippingDuration(),
                    self::TEST_MODE_KEY => $this->isInfinitePayTestMode()
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
    public function getInfinitePayClientRef()
    {
        return $this->infinitePayClientRef;
    }

    /**
     * @param string $clientRef
     * @return InfinitePaySettings
     */
    public function setInfinitePayClientRef($clientRef)
    {
        $this->infinitePayClientRef = $clientRef;
        return $this;
    }

    /**
     * @return string
     */
    public function getInfinitePayUsername()
    {
        return $this->infinitePayUsername;
    }

    /**
     * @param string $username
     * @return InfinitePaySettings
     */
    public function setInfinitePayUsername($username)
    {
        $this->infinitePayUsername = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getInfinitePayPassword()
    {
        return $this->infinitePayPassword;
    }

    /**
     * @param string $password
     * @return InfinitePaySettings
     */
    public function setInfinitePayPassword($password)
    {
        $this->infinitePayPassword = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getInfinitePaySecret()
    {
        return $this->infinitePaySecret;
    }

    /**
     * @param string $secret
     * @return InfinitePaySettings
     */
    public function setInfinitePaySecret($secret)
    {
        $this->infinitePaySecret = $secret;
        return $this;
    }

    /**
     * @return bool
     */
    public function isInfinitePayAutoCapture()
    {
        return $this->infinitePayAutoCapture;
    }

    /**
     * @param bool $autoCapture
     * @return InfinitePaySettings
     */
    public function setInfinitePayAutoCapture($autoCapture)
    {
        $this->infinitePayAutoCapture = $autoCapture;
        return $this;
    }

    /**
     * @return bool
     */
    public function isInfinitePayAutoActivate()
    {
        return $this->infinitePayAutoActivate;
    }

    /**
     * @param bool $autoActivate
     * @return InfinitePaySettings
     */
    public function setInfinitePayAutoActivate($autoActivate)
    {
        $this->infinitePayAutoActivate = $autoActivate;
        return $this;
    }

    /**
     * @return bool
     */
    public function isInfinitePayTestMode()
    {
        return $this->infinitePayTestMode;
    }

    /**
     * @param bool $testMode
     * @return InfinitePaySettings
     */
    public function setInfinitePayTestMode($testMode)
    {
        $this->infinitePayTestMode = $testMode;
        return $this;
    }


    /**
     * @return bool
     */
    public function isInfinitePayDebugMode()
    {
        return $this->infinitePayDebugMode;
    }

    /**
     * @param bool $debugMode
     * @return InfinitePaySettings
     */
    public function setInfinitePayDebugMode($debugMode)
    {
        $this->infinitePayDebugMode = $debugMode;
        return $this;
    }

    /**
     * @return int
     */
    public function getInfinitePayInvoiceDuePeriod()
    {
        return $this->infinitePayInvoiceDuePeriod;
    }

    /**
     * @param int $invoiceDuePeriod
     * @return InfinitePaySettings
     */
    public function setInfinitePayInvoiceDuePeriod($invoiceDuePeriod)
    {
        $this->infinitePayInvoiceDuePeriod = $invoiceDuePeriod;
        return $this;
    }

    /**
     * @return int
     */
    public function getInfinitePayInvoiceShippingDuration()
    {
        return $this->infinitePayInvoiceShippingDuration;
    }

    /**
     * @param int $invoiceShippingDuration
     * @return InfinitePaySettings
     */
    public function setInfinitePayInvoiceShippingDuration($invoiceShippingDuration)
    {
        $this->infinitePayInvoiceShippingDuration = $invoiceShippingDuration;
        return $this;
    }
}
