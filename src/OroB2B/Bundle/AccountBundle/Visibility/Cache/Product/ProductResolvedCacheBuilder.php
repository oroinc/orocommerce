<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AccountBundle\Entity\Repository\ProductVisibilityResolvedRepository;

class ProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    const VISIBLE = 'visible';
    const HIDDEN = 'hidden';

    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelectExecutor
     */
    public function setInsertFromSelectExecutor(InsertFromSelectQueryExecutor $insertFromSelectExecutor)
    {
        $this->insertFromSelectExecutor = $insertFromSelectExecutor;
    }

    /**
     * @param string $cacheClass
     */
    public function setCacheClass($cacheClass)
    {
        $this->cacheClass = $cacheClass;
    }

    /**
     * @param VisibilityInterface|ProductVisibility $productVisibility
     */
    public function resolveVisibilitySettings(VisibilityInterface $productVisibility)
    {
        $product = $productVisibility->getProduct();
        $website = $productVisibility->getWebsite();

        $selectedVisibility = $productVisibility->getVisibility();

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $productVisibilityResolved = $em
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->findByPrimaryKey($product, $website);

        if (!$productVisibilityResolved && $selectedVisibility !== ProductVisibility::CONFIG) {
            $productVisibilityResolved = new ProductVisibilityResolved($website, $product);
            $em->persist($productVisibilityResolved);
        }

        if ($selectedVisibility === ProductVisibility::CATEGORY) {
            $category = $this->registry
                ->getManagerForClass('OroB2BCatalogBundle:Category')
                ->getRepository('OroB2BCatalogBundle:Category')
                ->findOneByProduct($product);

            if ($category) {
                $productVisibilityResolved->setSourceProductVisibility(null);
                $productVisibilityResolved->setVisibility(
                    $this->convertVisibility($this->categoryVisibilityResolver->isCategoryVisible($category))
                );
                $productVisibilityResolved->setSource(BaseProductVisibilityResolved::SOURCE_CATEGORY);
                $productVisibilityResolved->setCategory($category);
            } else {
                $this->resolveConfigValue($productVisibilityResolved);
            }
        } elseif ($selectedVisibility === ProductVisibility::CONFIG) {
            if ($productVisibilityResolved) {
                $em->remove($productVisibilityResolved);
            }
        } else {
            $this->resolveStaticValues($productVisibilityResolved, $productVisibility, $selectedVisibility);
        }

        // set calculated visibility to account resolved values
        if ($productVisibilityResolved && $selectedVisibility !== ProductVisibility::CONFIG) {
            $visibility = $productVisibilityResolved->getVisibility();
        } else {
            $visibility = $this->getVisibilityFromConfig();
        }
        $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->updateCurrentProductRelatedEntities($website, $product, $visibility);
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface$visibilitySettings)
    {
        return $visibilitySettings instanceof ProductVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function productCategoryChanged(Product $product)
    {
        $category = $this->registry
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->findOneByProduct($product);
        $websites = $this->registry
            ->getManagerForClass('OroB2BWebsiteBundle:Website')
            ->getRepository('OroB2BWebsiteBundle:Website')
            ->findAll();
        $isCategoryVisible = null;
        if ($category) {
            $isCategoryVisible = $this->categoryVisibilityResolver->isCategoryVisible($category);
        }
        $this->getRepository()->deleteByProduct($product);
        $this->getRepository()->insertByProduct(
            $this->insertFromSelectExecutor,
            $product,
            $websites,
            $category,
            $isCategoryVisible
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        $manager = $this->getManager();
        $repository = $this->getRepository();

        $manager->beginTransaction();
        try {
            $repository->clearTable($website);
            $repository->insertFromBaseTable($this->insertFromSelectExecutor, $website);
            $categoriesGrouped = $this->getCategories();
            $repository->insertByCategory(
                $this->insertFromSelectExecutor,
                BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
                $categoriesGrouped[self::VISIBLE],
                $website
            );
            $repository->insertByCategory(
                $this->insertFromSelectExecutor,
                BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                $categoriesGrouped[self::HIDDEN],
                $website
            );
            $manager->commit();
        } catch (\Exception $exception) {
            $manager->rollback();
            throw $exception;
        }
    }

    /**
     * @return array
     */
    protected function getCategories()
    {
        // TODO: Fix after new interface for CategoryVisibilityResolver introduced in scope of BB-1647
        /** @var Category[] $categories */
        $categories = $this->registry->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select('category.id')
            ->getQuery()
            ->getScalarResult();

        return [self::VISIBLE => array_map('current', $categories), self::HIDDEN => []];
    }

    /**
     * @return ProductVisibilityResolvedRepository
     */
    protected function getRepository()
    {
        return $this->getManager()->getRepository($this->cacheClass);
    }

    /**
     * @return EntityManagerInterface|null
     */
    protected function getManager()
    {
        return $this->registry->getManagerForClass($this->cacheClass);
    }
}
