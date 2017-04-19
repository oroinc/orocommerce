<?php

namespace Oro\Bundle\ApruveBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\ApruveBundle\Entity\Repository\ApruveSettingsRepository")
 */
class ApruveSettings extends Transport
{
    /**
     * General keys.
     */
    const LABELS_KEY = 'labels';
    const SHORT_LABELS_KEY = 'short_labels';

    /**
     * Apruve-specific keys.
     */
    const MERCHANT_ID_KEY = 'metchant_id';
    const API_KEY_KEY = 'api_key';
    const WEBHOOK_TOKEN_KEY = 'webhook_token';
    const TEST_MODE_KEY = 'test_mode';

    /**
     * @var ParameterBag
     */
    private $settings;

    /**
     * @var bool
     *
     * @ORM\Column(name="apruve_test_mode", type="boolean", options={"default"=false})
     */
    private $apruveTestMode = false;

    /**
     * @var string
     *
     * @ORM\Column(name="apruve_merchant_id", type="string", length=255)
     */
    private $apruveMerchantId;

    /**
     * @var string
     *
     * @ORM\Column(name="apruve_api_key", type="string", length=255)
     */
    private $apruveApiKey;

    /**
     * @var string
     *
     * Used in webhook URL.
     *
     * @ORM\Column(name="apruve_webhook_token", type="string", length=255)
     */
    private $apruveWebhookToken;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_apruve_trans_label",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    private $labels;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_apruve_short_label",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    private $shortLabels;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->shortLabels = new ArrayCollection();
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return ApruveSettings
     */
    public function addLabel(LocalizedFallbackValue $label)
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return ApruveSettings
     */
    public function removeLabel(LocalizedFallbackValue $label)
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getShortLabels()
    {
        return $this->shortLabels;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return ApruveSettings
     */
    public function addShortLabel(LocalizedFallbackValue $label)
    {
        if (!$this->shortLabels->contains($label)) {
            $this->shortLabels->add($label);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return ApruveSettings
     */
    public function removeShortLabel(LocalizedFallbackValue $label)
    {
        if ($this->shortLabels->contains($label)) {
            $this->shortLabels->removeElement($label);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    self::LABELS_KEY => $this->getLabels()->toArray(),
                    self::SHORT_LABELS_KEY => $this->getShortLabels()->toArray(),
                    self::MERCHANT_ID_KEY => $this->getApruveMerchantId(),
                    self::API_KEY_KEY => $this->getApruveApiKey(),
                    self::TEST_MODE_KEY => $this->getApruveTestMode(),
                    self::WEBHOOK_TOKEN_KEY => $this->getApruveWebhookToken(),
                ]
            );
        }

        return $this->settings;
    }

    /**
     * @return bool
     */
    public function getApruveTestMode()
    {
        return $this->apruveTestMode;
    }

    /**
     * @param bool $testMode
     *
     * @return ApruveSettings
     */
    public function setApruveTestMode($testMode)
    {
        $this->apruveTestMode = $testMode;

        return $this;
    }

    /**
     * @return string
     */
    public function getApruveMerchantId()
    {
        return $this->apruveMerchantId;
    }

    /**
     * @param string $merchantId
     *
     * @return ApruveSettings
     */
    public function setApruveMerchantId($merchantId)
    {
        $this->apruveMerchantId = $merchantId;

        return $this;
    }

    /**
     * @return string
     */
    public function getApruveApiKey()
    {
        return $this->apruveApiKey;
    }

    /**
     * @param string $apiKey
     *
     * @return ApruveSettings
     */
    public function setApruveApiKey($apiKey)
    {
        $this->apruveApiKey = $apiKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getApruveWebhookToken()
    {
        return $this->apruveWebhookToken;
    }

    /**
     * @param string $webhookToken
     *
     * @return ApruveSettings
     */
    public function setApruveWebhookToken($webhookToken)
    {
        $this->apruveWebhookToken = $webhookToken;

        return $this;
    }
}
