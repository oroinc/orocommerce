<?php

namespace OroB2B\Bundle\ShippingBundle\Provider;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;

class ShippingOptionsProvider
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigManager */
    protected $configManager;

    /** @var string */
    protected $weightUnitClass = 'OroB2B\Bundle\ShippingBundle\Entity\WeightUnit';

    /** @var string */
    protected $lengthUnitClass = 'OroB2B\Bundle\ShippingBundle\Entity\LengthUnit';

    /** @var string */
    protected $freightClassClass = 'OroB2B\Bundle\ShippingBundle\Entity\FreightClass';

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    /**
     * @param $weightUnitClass
     */
    public function setWeightUnitClass($weightUnitClass)
    {
        $this->weightUnitClass = $weightUnitClass;
    }

    /**
     * @param $lengthUnitClass
     */
    public function setLengthUnitClass($lengthUnitClass)
    {
        $this->lengthUnitClass = $lengthUnitClass;
    }

    /**
     * @param $freightClassClass
     */
    public function setFreightClassClass($freightClassClass)
    {
        $this->freightClassClass = $freightClassClass;
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
     * @param bool $onlyEnabled
     *
     * @return WeightUnit[]
     */
    public function getWeightUnits($onlyEnabled = true)
    {
        return $this->getRepositoryForClass($this->weightUnitClass)->findAll();
    }

    /**
     * @param bool $onlyEnabled
     *
     * @return LengthUnit[]
     */
    public function getLengthUnits($onlyEnabled = true)
    {
        return $this->getRepositoryForClass($this->lengthUnitClass)->findAll();
    }

    /**
     * @param bool $onlyEnabled
     *
     * @return FreightClass[]
     */
    public function getFreightClasses($onlyEnabled = true)
    {
        return $this->getRepositoryForClass($this->freightClassClass)->findAll();
    }
}
