<?php

namespace Oro\Bundle\UPSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Entity that represents UPS Transport
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
#[ORM\Entity]
class UPSTransport extends Transport
{
    public const PICKUP_TYPE_REGULAR_DAILY = '01';
    public const PICKUP_TYPE_CUSTOMER_COUNTER = '03';
    public const PICKUP_TYPE_ONE_TIME = '06';
    public const PICKUP_TYPE_ON_CALL_AIR = '07';
    public const PICKUP_TYPE_LETTER_CENTER = '19';

    public const UNIT_OF_WEIGHT_KGS = 'KGS';
    public const UNIT_OF_WEIGHT_LBS = 'LBS';

    public const UNIT_OF_LENGTH_INCH = 'IN';
    public const UNIT_OF_LENGTH_CM = 'CM';

    #[ORM\Column(name: 'ups_test_mode', type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    protected ?bool $upsTestMode = false;

    #[ORM\Column(name: 'ups_client_id', type: Types::STRING, length: 255)]
    private ?string $upsClientId = null;

    #[ORM\Column(name: 'ups_client_secret', type: Types::STRING, length: 255)]
    private ?string $upsClientSecret = null;

    #[ORM\Column(name: 'ups_access_token', type: Types::TEXT)]
    private ?string $upsAccessToken = null;

    #[ORM\Column(name: 'ups_access_token_expires', type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $upsAccessTokenExpiresAt = null;

    #[ORM\Column(name: 'ups_api_user', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $upsApiUser = null;

    #[ORM\Column(name: 'ups_api_password', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $upsApiPassword = null;

    #[ORM\Column(name: 'ups_api_key', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $upsApiKey = null;

    #[ORM\Column(name: 'ups_shipping_account_number', type: Types::STRING, length: 100, nullable: false)]
    protected ?string $upsShippingAccountNumber = null;

    #[ORM\Column(name: 'ups_shipping_account_name', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $upsShippingAccountName = null;

    #[ORM\Column(name: 'ups_pickup_type', type: Types::STRING, length: 2, nullable: false)]
    protected ?string $upsPickupType = null;

    #[ORM\Column(name: 'ups_unit_of_weight', type: Types::STRING, length: 3, nullable: false)]
    protected ?string $upsUnitOfWeight = null;

    #[ORM\ManyToOne(targetEntity: Country::class)]
    #[ORM\JoinColumn(name: 'ups_country_code', referencedColumnName: 'iso2_code')]
    protected ?Country $upsCountry = null;

    /**
     * @var Collection<int, ShippingService>
     */
    #[ORM\ManyToMany(targetEntity: ShippingService::class, fetch: 'EAGER')]
    #[ORM\JoinTable(name: 'oro_ups_transport_ship_service')]
    #[ORM\JoinColumn(name: 'transport_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'ship_service_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $applicableShippingServices = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_ups_transport_label')]
    #[ORM\JoinColumn(name: 'transport_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    protected ?Collection $labels = null;

    #[ORM\Column(name: 'ups_invalidate_cache_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $upsInvalidateCacheAt = null;

    protected ?ParameterBag $settings = null;

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
                    'labels' => $this->getLabels()->toArray(),
                    'client_id' => $this->getUpsClientId(),
                    'client_secret' => $this->getUpsClientSecret()
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
     * @return \DateTime|null
     */
    public function getUpsInvalidateCacheAt(): ?\DateTime
    {
        return $this->upsInvalidateCacheAt;
    }

    public function getUpsClientId(): ?string
    {
        return $this->upsClientId;
    }

    public function setUpsClientId(?string $upsClientId): self
    {
        $this->upsClientId = $upsClientId;

        return $this;
    }

    public function getUpsClientSecret(): ?string
    {
        return $this->upsClientSecret;
    }

    public function setUpsClientSecret(?string $upsClientSecret): self
    {
        $this->upsClientSecret = $upsClientSecret;

        return $this;
    }

    public function getUpsAccessToken(): ?string
    {
        return $this->upsAccessToken;
    }

    public function setUpsAccessToken(?string $upsAccessToken): void
    {
        $this->upsAccessToken = $upsAccessToken;
    }

    public function getUpsAccessTokenExpiresAt(): ?\DateTime
    {
        return $this->upsAccessTokenExpiresAt;
    }

    public function setUpsAccessTokenExpiresAt(?\DateTime $upsAccessTokenExpiresAt): void
    {
        $this->upsAccessTokenExpiresAt = $upsAccessTokenExpiresAt;
    }
}
