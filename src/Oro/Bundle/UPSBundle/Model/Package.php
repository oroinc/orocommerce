<?php

namespace Oro\Bundle\UPSBundle\Model;

/**
 * UPS Package model
 */
class Package
{
    public const PACKAGING_TYPE_CODE = '00';

    protected ?string $packagingTypeCode = null;
    protected ?string $weightCode = null;
    protected ?string $weight = null;

    public function toArray(): array
    {
        return [
            'PackagingType' => [
                'Code' => $this->packagingTypeCode,
            ],
            'PackageWeight' => [
                'UnitOfMeasurement' => [
                    'Code' => (string)$this->weightCode,
                ],
                'Weight' => (string)$this->weight,
            ],
        ];
    }

    public static function create(string $unitOfWeight, float|int $weight): self
    {
        return (new Package())
            ->setPackagingTypeCode(self::PACKAGING_TYPE_CODE)
            ->setWeightCode($unitOfWeight)
            ->setWeight($weight)
        ;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function getPackagingTypeCode(): ?string
    {
        return $this->packagingTypeCode;
    }

    public function setPackagingTypeCode(string $packagingTypeCode): self
    {
        $this->packagingTypeCode = $packagingTypeCode;

        return $this;
    }

    public function getWeightCode(): ?string
    {
        return $this->weightCode;
    }

    public function setWeightCode(string $weightCode): self
    {
        $this->weightCode = $weightCode;

        return $this;
    }

    public function getWeight(): ?string
    {
        return $this->weight;
    }

    public function setWeight(string $weight): self
    {
        $this->weight = $weight;

        return $this;
    }
}
