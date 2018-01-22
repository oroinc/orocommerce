<?php

namespace Oro\Bundle\FedexShippingBundle\Model;

class FedexPackageSettings implements FedexPackageSettingsInterface
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
}
