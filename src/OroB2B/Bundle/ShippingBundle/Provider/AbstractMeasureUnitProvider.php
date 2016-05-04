<?php

namespace OroB2B\Bundle\ShippingBundle\Provider;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;

class AbstractMeasureUnitProvider
{
    /**
     * Units Entity Class
     *
     * @var string
     */
    protected $entityClass;

    /**
     * System config entry name
     *
     * @var string
     */
    protected $configEntryName;

    /** @var UnitLabelFormatter */
    protected $labelFormatter;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigManager */
    protected $configManager;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param string $configEntryName
     */
    public function setConfigEntryName($configEntryName)
    {
        $this->configEntryName = $configEntryName;
    }

    /**
     * @param UnitLabelFormatter $labelFormatter
     */
    public function setLabelFormatter(UnitLabelFormatter $labelFormatter)
    {
        $this->labelFormatter = $labelFormatter;
    }

    /**
     * Returns list of units, if $onlyEnabled is false then returns full list of registered units
     *
     * @param bool $onlyEnabled
     *
     * @return MeasureUnitInterface[]|array
     */
    public function getUnits($onlyEnabled = true)
    {
        $dbUnits = $this->getRepositoryForClass($this->entityClass)->findAll();

        if ($onlyEnabled) {
            $configCodes = $this->getSysConfigValues();
            $dbUnits = array_filter($dbUnits, function (MeasureUnitInterface $item) use ($configCodes) {
                return in_array($item->getCode(), $configCodes);
            });
        }

        return $dbUnits;
    }

    /**
     * @param bool $onlyEnabled
     * @return array
     */
    public function getUnitsCodes($onlyEnabled = true)
    {
        $codes = array_map(
            function (MeasureUnitInterface $entity) {
                return $entity->getCode();
            },
            $this->getUnits($onlyEnabled)
        );

        return $codes;
    }

    /**
     * @param array $codes
     * @param bool $isShort
     * @return array
     */
    public function formatUnitsCodes(array $codes = [], $isShort = false)
    {
        ksort($codes);

        return array_map(
            function ($code) use ($isShort) {
                return $this->labelFormatter->format($code, $isShort);
            },
            $codes
        );
    }

    /**
     * @param $class
     *
     * @return EntityRepository
     */
    protected function getRepositoryForClass($class)
    {
        return $this->doctrineHelper->getEntityRepositoryForClass($class);
    }

    /**
     * @param bool $default
     *
     * @return mixed|null
     */
    protected function getSysConfigValues($default = false)
    {
        return $this->configManager->get($this->configEntryName, $default);
    }
}
