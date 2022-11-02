<?php

namespace Oro\Bundle\FedexShippingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * FedexIntegrationSettings ORM entity.
 *
 * @ORM\Entity
 */
class FedexIntegrationSettings extends Transport
{
    const PICKUP_TYPE_REGULAR = 'REGULAR_PICKUP';
    const PICKUP_TYPE_REQUEST_COURIER = 'REQUEST_COURIER';
    const PICKUP_TYPE_DROP_BOX = 'DROP_BOX';
    const PICKUP_TYPE_BUSINESS_SERVICE_CENTER = 'BUSINESS_SERVICE_CENTER';
    const PICKUP_TYPE_STATION = 'STATION';

    const UNIT_OF_WEIGHT_KG = 'KG';
    const UNIT_OF_WEIGHT_LB = 'LB';

    const DIMENSION_CM = 'CM';
    const DIMENSION_IN = 'IN';

    /**
     * @var bool
     *
     * @ORM\Column(name="fedex_test_mode", type="boolean", options={"default"=false})
     */
    private $fedexTestMode;

    /**
     * @var string
     *
     * @ORM\Column(name="fedex_key", type="string", length=100)
     */
    private $key;

    /**
     * @var string
     *
     * @ORM\Column(name="fedex_password", type="string", length=100)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="fedex_account_number", type="string", length=100)
     */
    private $accountNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="fedex_meter_number", type="string", length=100)
     */
    private $meterNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="fedex_pickup_type", type="string", length=100)
     */
    private $pickupType;

    /**
     * @var string
     *
     * @ORM\Column(name="fedex_unit_of_weight", type="string", length=3)
     */
    private $unitOfWeight;

    /**
     * @var Collection|FedexShippingService[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="FedexShippingService",
     *      fetch="EAGER"
     * )
     * @ORM\JoinTable(
     *      name="oro_fedex_transp_ship_service",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="ship_service_id", referencedColumnName="id", onDelete="CASCADE")
     *      }
     * )
     */
    private $shippingServices;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_fedex_transport_label",
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
     * @var \DateTime|null $invalidateCacheAt
     *
     * @ORM\Column(name="fedex_invalidate_cache_at", type="datetime")
     */
    private $invalidateCacheAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="fedex_ignore_package_dimension", type="boolean", options={"default"=false})
     */
    private $ignorePackageDimensions = false;

    public function __construct()
    {
        $this->shippingServices = new ArrayCollection();
        $this->labels = new ArrayCollection();
    }

    /**
     * @return bool
     */
    public function isFedexTestMode()
    {
        return $this->fedexTestMode;
    }

    public function setFedexTestMode(bool $testMode): self
    {
        $this->fedexTestMode = $testMode;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccountNumber()
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(string $accountNumber): self
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getMeterNumber()
    {
        return $this->meterNumber;
    }

    public function setMeterNumber(string $meterNumber): self
    {
        $this->meterNumber = $meterNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getPickupType()
    {
        return $this->pickupType;
    }

    public function setPickupType(string $pickupType): self
    {
        $this->pickupType = $pickupType;

        return $this;
    }

    /**
     * @return string
     */
    public function getUnitOfWeight()
    {
        return $this->unitOfWeight;
    }

    public function setUnitOfWeight(string $unitOfWeight): self
    {
        $this->unitOfWeight = $unitOfWeight;

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getLabels(): Collection
    {
        return $this->labels;
    }

    public function addLabel(LocalizedFallbackValue $label): self
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
        }

        return $this;
    }

    public function removeLabel(LocalizedFallbackValue $label): self
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
        }

        return $this;
    }

    /**
     * @return Collection|FedexShippingService[]
     */
    public function getShippingServices(): Collection
    {
        return $this->shippingServices;
    }

    public function addShippingService(FedexShippingService $service): self
    {
        if (!$this->shippingServices->contains($service)) {
            $this->shippingServices->add($service);
        }

        return $this;
    }

    public function removeShippingService(FedexShippingService $service): self
    {
        if ($this->shippingServices->contains($service)) {
            $this->shippingServices->removeElement($service);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsBag()
    {
        return new ParameterBag();
    }

    public function getDimensionsUnit(): string
    {
        if ($this->getUnitOfWeight() === FedexIntegrationSettings::UNIT_OF_WEIGHT_LB) {
            return self::DIMENSION_IN;
        }

        return self::DIMENSION_CM;
    }

    public function setInvalidateCacheAt(\DateTime $invalidateCacheAt = null): self
    {
        $this->invalidateCacheAt = $invalidateCacheAt;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getInvalidateCacheAt()
    {
        return $this->invalidateCacheAt;
    }

    public function isIgnorePackageDimensions(): bool
    {
        return $this->ignorePackageDimensions;
    }

    public function setIgnorePackageDimensions(bool $ignorePackageDimensions): self
    {
        $this->ignorePackageDimensions = $ignorePackageDimensions;

        return $this;
    }
}
