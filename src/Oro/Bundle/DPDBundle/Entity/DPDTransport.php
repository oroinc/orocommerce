<?php

namespace Oro\Bundle\DPDBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @ORM\Entity
 */
class DPDTransport extends Transport
{
    const LABEL_SIZE_OPTION = 'label_size';
    const PDF_A4_LABEL_SIZE = 'PDF_A4';
    const PDF_A6_LABEL_SIZE = 'PDF_A6';

    const LABEL_START_POSTITION_OPTION = 'label_start_position';
    const UPPERLEFT_LABEL_START_POSITION = 'UpperLeft';
    const UPPERRIGHT_LABEL_START_POSITION = 'UpperRight';
    const LOWERLEFT_LABEL_START_POSITION = 'LowerLeft';
    const LOWERRIGHT_LABEL_START_POSITION = 'LowerRight';

    /**
     * @var boolean
     *
     * @ORM\Column(name="dpd_live_mode", type="boolean", nullable=false)
     */
    protected $liveMode;

    /**
     * @var string
     *
     * @ORM\Column(name="dpd_cloud_user_id", type="string", length=255, nullable=false)
     */
    protected $cloudUserId;

    /**
     * @var string
     *
     * @ORM\Column(name="dpd_cloud_user_token", type="string", length=255, nullable=false)
     */
    protected $cloudUserToken;

    /**
     * @var Collection|ShippingService[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="ShippingService",
     *     fetch="EAGER"
     * )
     * @ORM\JoinTable(
     *      name="oro_dpd_transport_ship_service",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="ship_service_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     */
    protected $applicableShippingServices;

    /**
     * @var string
     *
     * @ORM\Column(name="dpd_label_size", type="string", length=10, nullable=false)
     */
    protected $labelSize;

    /**
     * @var string
     *
     * @ORM\Column(name="dpd_label_start_position", type="string", length=20, nullable=false)
     */
    protected $labelStartPosition;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_dpd_transport_label",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $labels;

    /**
     * @var \DateTime $invalidateCacheAt
     *
     * @ORM\Column(name="dpd_invalidate_cache_at", type="datetime", nullable=true)
     */
    protected $invalidateCacheAt;

    /**
     * @var ParameterBag
     */
    protected $settings;

    public function __construct()
    {
        $this->applicableShippingServices = new ArrayCollection();
        $this->labels = new ArrayCollection();
    }

    /**
     * @return boolean
     */
    public function getLiveMode()
    {
        return $this->liveMode;
    }

    /**
     * @param boolean $liveMode
     * @return DPDTransport
     */
    public function setLiveMode($liveMode)
    {
        $this->liveMode = $liveMode;
        return $this;
    }



    /**
     * @return string
     */
    public function getCloudUserId()
    {
        return $this->cloudUserId;
    }

    /**
     * @param string $cloudUserId
     * @return DPDTransport
     */
    public function setCloudUserId($cloudUserId)
    {
        $this->cloudUserId = $cloudUserId;
        return $this;
    }

    /**
     * @return string
     */
    public function getCloudUserToken()
    {
        return $this->cloudUserToken;
    }

    /**
     * @param string $cloudUserToken
     * @return DPDTransport
     */
    public function setCloudUserToken($cloudUserToken)
    {
        $this->cloudUserToken = $cloudUserToken;
        return $this;
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
     * @return $this
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
     * @return $this
     */
    public function removeLabel(LocalizedFallbackValue $label)
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
        }

        return $this;
    }

    /**
     * @return Collection|ShippingService[]
     */
    public function getApplicableShippingServices()
    {
        return $this->applicableShippingServices;
    }

    /**
     * @param string $code
     *
     * @return ShippingService|null
     */
    public function getApplicableShippingService($code)
    {
        $result = null;

        foreach ($this->applicableShippingServices as $service) {
            if ($service->getCode() === $code) {
                $result = $service;
                break;
            }
        }

        return $result;
    }

    /**
     * @param ShippingService $service
     *
     * @return $this
     */
    public function addApplicableShippingService(ShippingService $service)
    {
        if (!$this->applicableShippingServices->contains($service)) {
            $this->applicableShippingServices->add($service);
        }

        return $this;
    }

    /**
     * @param ShippingService $service
     *
     * @return $this
     */
    public function removeApplicableShippingService(ShippingService $service)
    {
        if ($this->applicableShippingServices->contains($service)) {
            $this->applicableShippingServices->removeElement($service);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getLabelSize()
    {
        return $this->labelSize;
    }

    /**
     * @param string $labelSize
     * @return DPDTransport
     */
    public function setLabelSize($labelSize)
    {
        $this->labelSize = $labelSize;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabelStartPosition()
    {
        return $this->labelStartPosition;
    }

    /**
     * @param string $labelStartPosition
     * @return DPDTransport
     */
    public function setLabelStartPosition($labelStartPosition)
    {
        $this->labelStartPosition = $labelStartPosition;
        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    'live_mode' => $this->getLiveMode(),
                    'cloud_user_id' => $this->getCloudUserId(),
                    'cloud_user_token' => $this->getCloudUserToken(),
                    'invalidate_cache_at' => $this->getInvalidateCacheAt(),
                    'applicable_shipping_services' => $this->getApplicableShippingServices(),
                    'label_size' => $this->getLabelSize(),
                    'label_start_position' => $this->getLabelStartPosition(),
                    'labels' => $this->getLabels()->toArray()
                ]
            );
        }

        return $this->settings;
    }

    /**
     * Set invalidateCacheAt
     *
     * @param \DateTime $invalidateCacheAt
     *
     * @return $this
     */
    public function setInvalidateCacheAt(\DateTime $invalidateCacheAt)
    {
        $this->invalidateCacheAt = $invalidateCacheAt;

        return $this;
    }

    /**
     * Get invalidateCacheAt
     *
     * @return \DateTime
     */
    public function getInvalidateCacheAt()
    {
        return $this->invalidateCacheAt;
    }
}
