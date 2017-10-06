<?php

namespace Oro\Bundle\FedexShippingBundle\Model;

class FedexPackageSettings implements FedexPackageSettingsInterface
{
    const MAX_PACKAGE_WEIGHT_KGS = 70;
    const MAX_PACKAGE_WEIGHT_LBS = 150;

    const MAX_PACKAGE_LENGTH_INCH = 119;
    const MAX_PACKAGE_LENGTH_CM = 302.26;

    const MAX_PACKAGE_GIRTH_INCH = 165;
    const MAX_PACKAGE_GIRTH_CM = 419.1;

    /**
     * @var float
     */
    private $maxWeight;

    /**
     * @var float
     */
    private $maxLength;

    /**
     * @var float
     */
    private $maxGirth;

    /**
     * @var string
     */
    private $unitOfWeight;

    /**
     * @var string
     */
    private $dimensionsUnit;

    /**
     * @param float  $maxWeight
     * @param float  $maxLength
     * @param float  $maxGirth
     * @param string $unitOfWeight
     * @param string $dimensionsUnit
     */
    public function __construct(
        float $maxWeight,
        float $maxLength,
        float $maxGirth,
        string $unitOfWeight,
        string $dimensionsUnit
    ) {
        $this->maxWeight = $maxWeight;
        $this->maxLength = $maxLength;
        $this->maxGirth = $maxGirth;
        $this->unitOfWeight = $unitOfWeight;
        $this->dimensionsUnit = $dimensionsUnit;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxWeight(): float
    {
        return $this->maxWeight;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxLength(): float
    {
        return $this->maxLength;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxGirth(): float
    {
        return $this->maxGirth;
    }

    /**
     * {@inheritDoc}
     */
    public function getUnitOfWeight(): string
    {
        return $this->unitOfWeight;
    }

    /**
     * {@inheritDoc}
     */
    public function getDimensionsUnit(): string
    {
        return $this->dimensionsUnit;
    }
}
