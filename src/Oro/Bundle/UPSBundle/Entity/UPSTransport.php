<?php

namespace Oro\Bundle\UPSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @ORM\Entity
 */
class UPSTransport extends Transport
{
    const PICKUP_TYPE_REGULAR_DAILY = '01';
    const PICKUP_TYPE_CUSTOMER_COUNTER = '03';
    const PICKUP_TYPE_ONE_TIME = '06';
    const PICKUP_TYPE_ON_CALL_AIR = '07';
    const PICKUP_TYPE_LETTER_CENTER = '19';

    const UNIT_OF_WEIGHT_KGS = 'KGS';
    const UNIT_OF_WEIGHT_LBS = 'LBS';

    const UNIT_OF_LENGTH_INCH = 'IN';
    const UNIT_OF_LENGTH_CM = 'CM';

    /**
     * @var bool
     *
     * @ORM\Column(name="ups_test_mode", type="boolean", nullable=false, options={"default"=false})
     */
    protected $upsTestMode = false;

    /**
     * @var string
     *
     * @ORM\Column(name="ups_api_user", type="string", length=255, nullable=false)
     */
    protected $upsApiUser;

    /**
     * @var string
     *
     * @ORM\Column(name="ups_api_password", type="string", length=255, nullable=false)
     */
    protected $upsApiPassword;

    /**
     * @var string
     *
     * @ORM\Column(name="ups_api_key", type="string", length=255, nullable=false)
     */
    protected $upsApiKey;

    /**
     * @var string
     *
     * @ORM\Column(name="ups_shipping_account_number", type="string", length=100, nullable=false)
     */
    protected $upsShippingAccountNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="ups_shipping_account_name", type="string", length=255, nullable=false)
     */
    protected $upsShippingAccountName;

    /**
     * @var string
     *
     * @ORM\Column(name="ups_pickup_type", type="string", length=2, nullable=false)
     */
    protected $upsPickupType;

    /**
     * @var string
     *
     * @ORM\Column(name="ups_unit_of_weight", type="string", length=3, nullable=false)
     */
    protected $upsUnitOfWeight;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AddressBundle\Entity\Country")
     * @ORM\JoinColumn(name="ups_country_code", referencedColumnName="iso2_code")
     */
    protected $upsCountry;

    /**
     * @var Collection|ShippingService[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="ShippingService",
     *     fetch="EAGER"
     * )
     * @ORM\JoinTable(
     *      name="oro_ups_transport_ship_service",
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
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_ups_transport_label",
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
     * @ORM\Column(name="ups_invalidate_cache_at", type="datetime", nullable=true)
     */
    protected $upsInvalidateCacheAt;

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
     * @return bool
     */
    public function isUpsTestMode()
    {
        return $this->upsTestMode;
    }

    /**
     * @param bool $testMode
     *
     * @return $this
     */
    public function setUpsTestMode($testMode)
    {
        $this->upsTestMode = $testMode;

        return $this;
    }

    /**
     * @param string $apiUser
     *
     * @return $this
     */
    public function setUpsApiUser($apiUser)
    {
        $this->upsApiUser = $apiUser;

        return $this;
    }

    /**
     * @return string
     */
    public function getUpsApiUser()
    {
        return $this->upsApiUser;
    }

    /**
     * @param string $apiPassword
     *
     * @return $this
     */
    public function setUpsApiPassword($apiPassword)
    {
        $this->upsApiPassword = $apiPassword;

        return $this;
    }

    /**
     * @return string
     */
    public function getUpsApiPassword()
    {
        return $this->upsApiPassword;
    }

    /**
     * @param string $apiKey
     *
     * @return $this
     */
    public function setUpsApiKey($apiKey)
    {
        $this->upsApiKey = $apiKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getUpsApiKey()
    {
        return $this->upsApiKey;
    }

    /**
     * @param string $shippingAccountNumber
     *
     * @return $this
     */
    public function setUpsShippingAccountNumber($shippingAccountNumber)
    {
        $this->upsShippingAccountNumber = $shippingAccountNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getUpsShippingAccountNumber()
    {
        return $this->upsShippingAccountNumber;
    }

    /**
     * @param string $shippingAccountName
     *
     * @return $this
     */
    public function setUpsShippingAccountName($shippingAccountName)
    {
        $this->upsShippingAccountName = $shippingAccountName;

        return $this;
    }

    /**
     * @return string
     */
    public function getUpsShippingAccountName()
    {
        return $this->upsShippingAccountName;
    }

    /**
     * @param string $pickupType
     *
     * @return $this
     */
    public function setUpsPickupType($pickupType)
    {
        $this->upsPickupType = $pickupType;

        return $this;
    }

    /**
     * @return string
     */
    public function getUpsPickupType()
    {
        return $this->upsPickupType;
    }

    /**
     * @param string $unitOfWeight
     *
     * @return $this
     */
    public function setUpsUnitOfWeight($unitOfWeight)
    {
        $this->upsUnitOfWeight = $unitOfWeight;

        return $this;
    }

    /**
     * @return string
     */
    public function getUpsUnitOfWeight()
    {
        return $this->upsUnitOfWeight;
    }

    /**
     * @param Country|null $country
     *
     * @return $this
     */
    public function setUpsCountry(Country $country = null)
    {
        $this->upsCountry = $country;
        return $this;
    }

    /**
     * @return Country
     */
    public function getUpsCountry()
    {
        return $this->upsCountry;
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
     * {@inheritdoc}
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    'api_user' => $this->getUpsApiUser(),
                    'api_password' => $this->getUpsApiPassword(),
                    'api_key' => $this->getUpsApiKey(),
                    'test_mode' => $this->isUpsTestMode(),
                    'shipping_account_name' => $this->getUpsShippingAccountName(),
                    'shipping_account_number' => $this->getUpsShippingAccountNumber(),
                    'pickup_type' => $this->getUpsPickupType(),
                    'unit_of_weight' => $this->getUpsUnitOfWeight(),
                    'country' => $this->getUpsCountry(),
                    'invalidate_cache_at' => $this->getUpsInvalidateCacheAt(),
                    'applicable_shipping_services' => $this->getApplicableShippingServices()->toArray(),
                    'labels' => $this->getLabels()->toArray()
                ]
            );
        }

        return $this->settings;
    }

    /**
     * Set invalidateCacheAt
     *
     * @param \DateTime|null $invalidateCacheAt
     *
     * @return $this
     */
    public function setUpsInvalidateCacheAt(\DateTime $invalidateCacheAt = null)
    {
        $this->upsInvalidateCacheAt = $invalidateCacheAt;

        return $this;
    }

    /**
     * Get invalidateCacheAt
     *
     * @return \DateTime
     */
    public function getUpsInvalidateCacheAt()
    {
        return $this->upsInvalidateCacheAt;
    }
}
