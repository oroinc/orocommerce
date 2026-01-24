<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;

/**
 * Provides measure units (length, weight, freight classes) filtered by system configuration.
 *
 * This provider retrieves measure units from the repository and filters them based on system configuration settings,
 * returning only enabled units when requested, which are available for use in shipping calculations
 * and product configuration.
 */
class MeasureUnitProvider
{
    /** @var ObjectRepository */
    protected $repository;

    /** @var ConfigManager */
    protected $configManager;

    /** @var string */
    protected $configEntryName;

    /**
     * @param ObjectRepository $repository
     * @param ConfigManager $configManager
     * @param string $configEntryName
     */
    public function __construct(
        ObjectRepository $repository,
        ConfigManager $configManager,
        $configEntryName
    ) {
        $this->repository = $repository;
        $this->configManager = $configManager;
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
}
