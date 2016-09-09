<?php

namespace Oro\Bundle\UPSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @ORM\Entity
 * @Oro\Loggable()
 */
class UPSTransport extends Transport
{
    const PICKUP_TYPES = [
        '01' => 'Regular Daily Pickup',
        '03' => 'Customer Counter',
        '06' => 'One Time Pickup',
        '07' => 'On Call Air',
        '19' => 'Letter Center'
    ];
    
    const UNIT_OF_WEIGHT_KGS = 'KGS';
    const UNIT_OF_WEIGHT_LBS = 'LBS';

    /**
     * @var string
     *
     * @ORM\Column(name="ups_base_url", type="string", length=255, nullable=false)
     * @Oro\Versioned()
     */
    protected $baseUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="ups_api_user", type="string", length=255, nullable=false)
     * @Oro\Versioned()
     */
    protected $apiUser;

    /**
     * @var string
     *
     * @ORM\Column(name="ups_api_password", type="string", length=255, nullable=false)
     * @Oro\Versioned()
     */
    protected $apiPassword;

    /**
     * @var string
     *
     * @ORM\Column(name="ups_api_key", type="string", length=255, nullable=false)
     */
    protected $apiKey;

    /**
     * @var string
     *
     * @ORM\Column(name="ups_shipping_account_number", type="string", length=100, nullable=false)
     */
    protected $shippingAccountNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="ups_shipping_account_name", type="string", length=255, nullable=false)
     */
    protected $shippingAccountName;

    /**
     * @var string
     *
     * @ORM\Column(name="ups_pickup_type", type="string", length=2, nullable=false)
     */
    protected $pickupType;

    /**
     * @var string
     *
     * @ORM\Column(name="ups_unit_of_weight", type="string", length=3, nullable=false)
     */
    protected $unitOfWeight;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AddressBundle\Entity\Country")
     * @ORM\JoinColumn(name="ups_country_code", referencedColumnName="iso2_code", nullable=false)
     */
    protected $country;

    /**
     * @var Collection|ShippingService[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="ShippingService"
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
     * @var ParameterBag
     */
    protected $settings;

    public function __construct()
    {
        $this->applicableShippingServices = new ArrayCollection();
    }

    /**
     * @param string $baseUrl
     *
     * @return $this
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param string $apiUser
     *
     * @return $this
     */
    public function setApiUser($apiUser)
    {
        $this->apiUser = $apiUser;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiUser()
    {
        return $this->apiUser;
    }

    /**
     * @param string $apiPassword
     *
     * @return $this
     */
    public function setApiPassword($apiPassword)
    {
        $this->apiPassword = $apiPassword;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiPassword()
    {
        return $this->apiPassword;
    }

    /**
     * @param string $apiKey
     *
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $shippingAccountNumber
     *
     * @return $this
     */
    public function setShippingAccountNumber($shippingAccountNumber)
    {
        $this->shippingAccountNumber = $shippingAccountNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getShippingAccountNumber()
    {
        return $this->shippingAccountNumber;
    }

    /**
     * @param string $shippingAccountName
     *
     * @return $this
     */
    public function setShippingAccountName($shippingAccountName)
    {
        $this->shippingAccountName = $shippingAccountName;

        return $this;
    }

    /**
     * @return string
     */
    public function getShippingAccountName()
    {
        return $this->shippingAccountName;
    }

    /**
     * @param string $pickupType
     *
     * @return $this
     */
    public function setPickupType($pickupType)
    {
        $this->pickupType = $pickupType;

        return $this;
    }

    /**
     * @return string
     */
    public function getPickupType()
    {
        return $this->pickupType;
    }

    /**
     * @param string $unitOfWeight
     *
     * @return $this
     */
    public function setUnitOfWeight($unitOfWeight)
    {
        $this->unitOfWeight = $unitOfWeight;

        return $this;
    }

    /**
     * @return string
     */
    public function getUnitOfWeight()
    {
        return $this->unitOfWeight;
    }

    /**
     * @param Country|null $country
     * @return $this
     */
    public function setCountry(Country $country = null)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
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
     * {@inheritdoc}
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    'api_user' => $this->getApiUser(),
                    'api_password' => $this->getApiPassword(),
                    'api_key' => $this->getApiKey(),
                    'base_url' => $this->getBaseUrl(),
                    'shipping_account_name' => $this->getShippingAccountName(),
                    'shipping_account_number' => $this->getShippingAccountNumber(),
                    'pickup_type' => $this->getPickupType(),
                    'unit_of_weight' => $this->getUnitOfWeight(),
                    'country' => $this->getCountry(),
                    'applicable_shipping_services' => $this->getApplicableShippingServices()->toArray(),
                ]
            );
        }

        return $this->settings;
    }
}
