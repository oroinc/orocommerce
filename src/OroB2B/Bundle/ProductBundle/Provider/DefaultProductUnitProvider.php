<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\CategoryUnitPrecision;

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

        if (null != $this->categoryId) {
            $categoryRepository = $this->getRepository('OroB2BCatalogBundle:Category');
            $category = $categoryRepository->findOneById($this->categoryId);

            do {
                $categoryUnitPrecision = $category->getUnitPrecision();

                if (null != $categoryUnitPrecision) {
                    $productUnitPrecision = new ProductUnitPrecision();
                    $productUnitPrecision
                        ->setUnit($categoryUnitPrecision->getUnit())
                        ->setPrecision($categoryUnitPrecision->getPrecision());
                    return $productUnitPrecision;
                }

                $category = $category->getParentCategory();
            } while (null != $category);

        }

        $defaultUnitValue = $this->configManager->get('orob2b_product.default_unit');
        $defaultUnitPrecision = $this->configManager->get('orob2b_product.default_unit_precision');

        $unit = $this
            ->getRepository('OroB2BProductBundle:ProductUnit')->findOneBy(['code' => $defaultUnitValue]);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setUnit($unit)
            ->setPrecision($defaultUnitPrecision);

        return $unitPrecision;
    }

    /**
     * @param string $className
     * @return EntityRepository
     * 
     */
    protected function getRepository($className)
    {
        return $this->registry
            ->getManagerForClass($className)
            ->getRepository($className);
    }
}

