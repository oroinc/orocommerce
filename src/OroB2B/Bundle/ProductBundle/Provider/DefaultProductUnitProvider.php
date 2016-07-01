<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\CatalogBundle\Model\CategoryUnitPrecision;

class DefaultProductUnitProvider
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var int
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
     * @param int $categoryId
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
            /** @var CategoryRepository $categoryRepository */
            $categoryRepository = $this->getRepository('OroB2BCatalogBundle:Category');
            /** @var Category $category */
            $category = $categoryRepository->findOneById($this->categoryId);
            do {
                /** @var CategoryUnitPrecision $categoryUnitPrecision */
                if ($category->getDefaultProductOptions()) {
                    $categoryUnitPrecision = $category->getDefaultProductOptions()->getUnitPrecision();
                }

                if (null != $categoryUnitPrecision) {
                    return $this->createProductUnitPrecision(
                        $categoryUnitPrecision->getUnit(),
                        $categoryUnitPrecision->getPrecision()
                    );
                }
                $category = $category->getParentCategory();
            } while (null != $category);
        }

        $defaultUnitValue = $this->configManager->get('orob2b_product.default_unit');
        $defaultUnitPrecision = $this->configManager->get('orob2b_product.default_unit_precision');

        $unit = $this
            ->getRepository('OroB2BProductBundle:ProductUnit')->findOneBy(['code' => $defaultUnitValue]);

        return $this->createProductUnitPrecision($unit, $defaultUnitPrecision);
    }

    /**
     * @param string $className
     * @return EntityRepository
     */
    protected function getRepository($className)
    {
        return $this->registry
            ->getManagerForClass($className)
            ->getRepository($className);
    }

    /**
     * @param ProductUnit $unit
     * @param int $precision
     * @return ProductUnitPrecision
     */
    protected function createProductUnitPrecision($unit, $precision)
    {
        $productUnitPrecision = new ProductUnitPrecision();
        return $productUnitPrecision->setUnit($unit)->setPrecision($precision);
    }
}
