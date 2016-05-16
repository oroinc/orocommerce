<?php

namespace OroB2B\Bundle\ShippingBundle\Provider;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;

class MeasureUnitProvider
{
    /** @var ObjectRepository */
    protected $repository;

    /** @var ConfigManager */
    protected $configManager;

    /** @var string */
    protected $configEntryName;

    /** @var UnitLabelFormatter */
    protected $labelFormatter;

    /**
     * @param ObjectRepository $repository
     * @param ConfigManager $configManager
     * @param UnitLabelFormatter $labelFormatter
     * @param string $configEntryName
     */
    public function __construct(
        ObjectRepository $repository,
        ConfigManager $configManager,
        UnitLabelFormatter $labelFormatter,
        $configEntryName
    ) {
        $this->repository = $repository;
        $this->configManager = $configManager;
        $this->labelFormatter = $labelFormatter;
        $this->configEntryName = $configEntryName;
    }

    /**
     * Returns list of units, if $onlyEnabled is false then returns full list of registered units
     *
     * @param bool $onlyEnabled
     * @return MeasureUnitInterface[]|array
     */
    public function getUnits($onlyEnabled = true)
    {
        $units = $this->repository->findAll();

        if ($onlyEnabled) {
            $configCodes = $this->configManager->get($this->configEntryName);
            $units = array_filter(
                $units,
                function (MeasureUnitInterface $item) use ($configCodes) {
                    return in_array($item->getCode(), $configCodes, true);
                }
            );
        }

        return $units;
    }

    /**
     * @param bool $isShort
     * @param bool $onlyEnabled
     * @return array
     */
    public function getFormattedUnits($isShort = false, $onlyEnabled = true)
    {
        return $this->labelFormatter->formatChoices($this->getUnits($onlyEnabled), $isShort);
    }
}
