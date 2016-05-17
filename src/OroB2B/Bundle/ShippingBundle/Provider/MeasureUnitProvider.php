<?php

namespace OroB2B\Bundle\ShippingBundle\Provider;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;

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
