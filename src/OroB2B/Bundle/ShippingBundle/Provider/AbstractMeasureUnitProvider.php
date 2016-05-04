<?php

namespace OroB2B\Bundle\ShippingBundle\Provider;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;

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
     * Returns list of units, if $onlyEnabled is false then returns full list of registered units
     *
     * @param bool $onlyEnabled
     *
     * @return MeasureUnitInterface[]|array
     */
    public function getUnits($onlyEnabled = true)
    {
        $dbUnits = $this->getRepositoryForClass($this->entityClass)->findAll();

        // Intersect with enabled from config if $enabled is true

        return $dbUnits;
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
