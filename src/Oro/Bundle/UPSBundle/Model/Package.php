<?php

namespace Oro\Bundle\UPSBundle\Model;

class Package
{
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
    public function setPackagingTypeCode(string $packagingTypeCode)
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
    public function setDimensionCode(string $dimensionCode)
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
    public function setDimensionLength(string $dimensionLength)
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
    public function setDimensionWidth(string $dimensionWidth)
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
    public function setDimensionHeight(string $dimensionHeight)
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
    public function setWeightCode(string $weightCode)
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
    public function setWeight(string $weight)
    {
        $this->weight = $weight;

        return $this;
    }
}
