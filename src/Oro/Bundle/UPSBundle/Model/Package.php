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
    protected $dimensionCode;

    /**
     * @var string
     */
    protected $dimensionLength;

    /**
     * @var string
     */
    protected $dimensionWidth;

    /**
     * @var string
     */
    protected $dimensionHeight;

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
            'Dimensions'    => [
                'UnitOfMeasurement' => [
                    'Code' => $this->dimensionCode,
                ],
                'Length'            => $this->dimensionLength,
                'Width'             => $this->dimensionWidth,
                'Height'            => $this->dimensionHeight,
            ],
            'PackageWeight' => [
                'UnitOfMeasurement' => [
                    'Code' => $this->weightCode,
                ],
                'Weight'            => $this->weight,
            ],
        ];
    }

    /**
     * @param string $dimensionCode
     * @param float $dimensionHeight
     * @param float $dimensionWidth
     * @param float $dimensionLength
     * @param string $unitOfWeight
     * @param float|int $weight
     * @return $this
     */
    public static function create(
        $dimensionCode,
        $dimensionHeight,
        $dimensionWidth,
        $dimensionLength,
        $unitOfWeight,
        $weight
    ) {
        return (new Package())
            ->setPackagingTypeCode(self::PACKAGING_TYPE_CODE)
            ->setDimensionCode($dimensionCode)
            ->setDimensionHeight($dimensionHeight)
            ->setDimensionWidth($dimensionWidth)
            ->setDimensionLength($dimensionLength)
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
    public function getDimensionCode()
    {
        return $this->dimensionCode;
    }

    /**
     * @param string $dimensionCode
     * @return $this
     */
    public function setDimensionCode($dimensionCode)
    {
        $this->dimensionCode = $dimensionCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getDimensionLength()
    {
        return $this->dimensionLength;
    }

    /**
     * @param string $dimensionLength
     * @return $this
     */
    public function setDimensionLength($dimensionLength)
    {
        $this->dimensionLength = $dimensionLength;

        return $this;
    }

    /**
     * @return string
     */
    public function getDimensionWidth()
    {
        return $this->dimensionWidth;
    }

    /**
     * @param string $dimensionWidth
     * @return $this
     */
    public function setDimensionWidth($dimensionWidth)
    {
        $this->dimensionWidth = $dimensionWidth;

        return $this;
    }

    /**
     * @return string
     */
    public function getDimensionHeight()
    {
        return $this->dimensionHeight;
    }

    /**
     * @param string $dimensionHeight
     * @return $this
     */
    public function setDimensionHeight($dimensionHeight)
    {
        $this->dimensionHeight = $dimensionHeight;

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
