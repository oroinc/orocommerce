<?php

namespace Oro\Bundle\FedexShippingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * FedexIntegrationSettings ORM entity.
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
#[ORM\Entity]
class FedexIntegrationSettings extends Transport
{
    public const PICKUP_TYPE_REGULAR = 'REGULAR_PICKUP';
    public const PICKUP_TYPE_REQUEST_COURIER = 'REQUEST_COURIER';
    public const PICKUP_TYPE_DROP_BOX = 'DROP_BOX';
    public const PICKUP_TYPE_BUSINESS_SERVICE_CENTER = 'BUSINESS_SERVICE_CENTER';
    public const PICKUP_TYPE_STATION = 'STATION';

    public const PICKUP_CONTACT_FEDEX_TO_SCHEDULE = 'CONTACT_FEDEX_TO_SCHEDULE';
    public const PICKUP_DROPOFF_AT_FEDEX_LOCATION = 'DROPOFF_AT_FEDEX_LOCATION';
    public const PICKUP_USE_SCHEDULED_PICKUP = 'USE_SCHEDULED_PICKUP';

    public const UNIT_OF_WEIGHT_KG = 'KG';
    public const UNIT_OF_WEIGHT_LB = 'LB';

    public const DIMENSION_CM = 'CM';
    public const DIMENSION_IN = 'IN';

    #[ORM\Column(name: 'fedex_test_mode', type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $fedexTestMode = false;

    #[ORM\Column(name: 'fedex_key', type: Types::STRING, length: 100)]
    private ?string $key = null;

    #[ORM\Column(name: 'fedex_password', type: Types::STRING, length: 100)]
    private ?string $password = null;

    #[ORM\Column(name: 'fedex_client_id', type: Types::STRING, length: 255)]
    private ?string $clientId = null;

    #[ORM\Column(name: 'fedex_client_secret', type: Types::STRING, length: 255)]
    private ?string $clientSecret = null;

    #[ORM\Column(name: 'fedex_access_token', type: Types::TEXT)]
    private ?string $accessToken = null;

    #[ORM\Column(name: 'fedex_access_token_expires', type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $accessTokenExpiresAt = null;

    #[ORM\Column(name: 'fedex_account_number', type: Types::STRING, length: 100)]
    private ?string $accountNumberSoap = null;

    #[ORM\Column(name: 'fedex_account_number_rest', type: Types::STRING, length: 100)]
    private ?string $accountNumber = null;

    #[ORM\Column(name: 'fedex_meter_number', type: Types::STRING, length: 100)]
    private ?string $meterNumber = null;

    #[ORM\Column(name: 'fedex_pickup_type', type: Types::STRING, length: 100)]
    private ?string $pickupTypeSoap = null;

    #[ORM\Column(name: 'fedex_pickup_type_rest', type: Types::STRING, length: 100)]
    private ?string $pickupType = null;

    #[ORM\Column(name: 'fedex_unit_of_weight', type: Types::STRING, length: 3)]
    private ?string $unitOfWeight = null;

    /**
     * @var Collection<int, FedexShippingService>
     */
    #[ORM\ManyToMany(targetEntity: FedexShippingService::class, fetch: 'EAGER')]
    #[ORM\JoinTable(name: 'oro_fedex_transp_ship_service')]
    #[ORM\JoinColumn(name: 'transport_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'ship_service_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Collection $shippingServices = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_fedex_transport_label')]
    #[ORM\JoinColumn(name: 'transport_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    private ?Collection $labels = null;

    #[ORM\Column(name: 'fedex_invalidate_cache_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $invalidateCacheAt = null;

    #[ORM\Column(name: 'fedex_ignore_package_dimension', type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $ignorePackageDimensions = false;

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

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): self
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(string $accountNumber): self
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    public function getAccountNumberSoap(): ?string
    {
        return $this->accountNumberSoap;
    }

    public function setAccountNumberSoap(string $accountNumber): self
    {
        $this->accountNumberSoap = $accountNumber;

        return $this;
    }

    public function getPickupType(): ?string
    {
        return $this->pickupType;
    }

    public function setPickupType(string $pickupType): self
    {
        $this->pickupType = $pickupType;

        return $this;
    }

    public function getPickupTypeSoap(): ?string
    {
        return $this->pickupTypeSoap;
    }

    public function setPickupTypeSoap(string $pickupTypeSoap): self
    {
        $this->pickupTypeSoap = $pickupTypeSoap;

        return $this;
    }

    public function getUnitOfWeight(): ?string
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

    #[\Override]
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

    public function setInvalidateCacheAt(?\DateTime $invalidateCacheAt = null): self
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

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessTokenExpiresAt(): ?\DateTime
    {
        return $this->accessTokenExpiresAt;
    }

    public function setAccessTokenExpiresAt(?\DateTime $accessTokenExpiresAt): void
    {
        $this->accessTokenExpiresAt = $accessTokenExpiresAt;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): void
    {
        $this->key = $key;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getMeterNumber(): ?string
    {
        return $this->meterNumber;
    }

    public function setMeterNumber(?string $meterNumber): void
    {
        $this->meterNumber = $meterNumber;
    }
}
