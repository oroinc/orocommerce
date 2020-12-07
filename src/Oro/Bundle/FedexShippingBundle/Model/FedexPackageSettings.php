<?php

namespace Oro\Bundle\FedexShippingBundle\Model;

/**
 * DTO that represents fedex package settings
 */
class FedexPackageSettings implements FedexPackageSettingsInterface, FedexPackageDimensionalSettingsInterface
{
    /**
     * @var string
     */
    private $unitOfWeight;

    /**
     * @var string
     */
    private $dimensionsUnit;

    /**
     * @var string
     */
    private $limitationExpression;

    /**
     * @var bool
     */
    private $ignorePackageDimensions;

    /**
     * @param string $unitOfWeight
     * @param string $dimensionsUnit
     * @param string $limitationExpression
     */
    public function __construct(
        string $unitOfWeight,
        string $dimensionsUnit,
        string $limitationExpression
    ) {
        $this->unitOfWeight = $unitOfWeight;
        $this->dimensionsUnit = $dimensionsUnit;
        $this->limitationExpression = $limitationExpression;
        $this->ignorePackageDimensions = false;
    }

    /**
     * @param bool $ignore
     */
    public function setIgnorePackageDimensions(bool $ignore)
    {
        $this->ignorePackageDimensions = $ignore;
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

    /**
     * {@inheritDoc}
     */
    public function getLimitationExpression(): string
    {
        return $this->limitationExpression;
    }

    /**
     * {@inheritDoc}
     */
    public function isDimensionsIgnored(): bool
    {
        return $this->ignorePackageDimensions;
    }
}
