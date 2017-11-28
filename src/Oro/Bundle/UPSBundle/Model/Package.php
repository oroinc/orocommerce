<?php

namespace Oro\Bundle\UPSBundle\Model;

class Package
{
    const PACKAGING_TYPE_CODE = '00';

    /**
     * @var string
     */
    protected $packagingTypeCode;

    /**
     * @var string
     */
    protected $weightCode;

    /**
     * @var string
     */
    protected $weight;

    /**
     * @return array
     */
    public function toArray()
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

    /**
     * @param string $unitOfWeight
     * @param float|int $weight
     * @return $this
     */
    public static function create(
        $unitOfWeight,
        $weight
    ) {
        return (new Package())
            ->setPackagingTypeCode(self::PACKAGING_TYPE_CODE)
            ->setWeightCode($unitOfWeight)
            ->setWeight($weight)
        ;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * @return string
     */
    public function getPackagingTypeCode()
    {
        return $this->packagingTypeCode;
    }

    /**
     * @param string $packagingTypeCode
     * @return $this
     */
    public function setPackagingTypeCode($packagingTypeCode)
    {
        $this->packagingTypeCode = $packagingTypeCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getWeightCode()
    {
        return $this->weightCode;
    }

    /**
     * @param string $weightCode
     * @return $this
     */
    public function setWeightCode($weightCode)
    {
        $this->weightCode = $weightCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param string $weight
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }
}
