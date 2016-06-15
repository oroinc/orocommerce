<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;

class DefaultProductUnitProvider
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var  ManagerRegistry
     */
    protected $registry;

    /**
     * @var  int
     */
    protected $categoryId;

    /**
     * @param ConfigManager $configManager
     * @param ManagerRegistry $registry
     */
    public function __construct(ConfigManager $configManager, ManagerRegistry $registry)
    {
        $this->configManager = $configManager;
        $this->registry = $registry;
    }

    /**
     * @param int $category
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    }
    
    /**
     * @return ProductUnitPrecision
     */
    public function getDefaultProductUnitPrecision()
    {
        $defaultUnitValue = $this->configManager->get('orob2b_product.default_unit');
        $defaultUnitPrecision = $this->configManager->get('orob2b_product.default_unit_precision');

        if($this->categoryId == 4) $defaultUnitValue = 'set';
        if($this->categoryId == 2) $defaultUnitValue = 'hour';
        $unit = $this
            ->getRepository()->findOneBy(['code' => $defaultUnitValue]);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setUnit($unit)
            ->setPrecision($defaultUnitPrecision);

        return $unitPrecision;
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getRepository()
    {
        return $this->registry
            ->getManagerForClass('OroB2B\Bundle\ProductBundle\Entity\ProductUnit')
            ->getRepository('OroB2B\Bundle\ProductBundle\Entity\ProductUnit');
    }
}
