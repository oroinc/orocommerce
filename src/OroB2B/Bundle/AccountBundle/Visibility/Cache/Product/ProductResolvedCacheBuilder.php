<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\ProductRepository;

class ProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder implements ProductCaseCacheBuilderInterface
{
    /**
     * @param VisibilityInterface|ProductVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $product = $visibilitySettings->getProduct();
        $website = $visibilitySettings->getWebsite();

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['website' => $website, 'product' => $product];

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $er = $em->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $hasProductVisibilityResolved = $er->hasEntity($where);

        if (!$hasProductVisibilityResolved && $selectedVisibility !== ProductVisibility::CONFIG) {
            $insert = true;
        }

        if ($selectedVisibility === ProductVisibility::CATEGORY) {
            $category = $this->registry
                ->getManagerForClass('OroB2BCatalogBundle:Category')
                ->getRepository('OroB2BCatalogBundle:Category')
                ->findOneByProduct($product);
            if ($category) {
                $update = [
                    'sourceProductVisibility' => null,
                    'visibility' => $this->convertVisibility(
                        $this->categoryVisibilityResolver->isCategoryVisible($category)
                    ),
                    'source' => BaseProductVisibilityResolved::SOURCE_CATEGORY,
                    'category' => $category
                ];
            } else {
                $update = $this->resolveConfigValue($visibilitySettings);
            }
        } elseif ($selectedVisibility === ProductVisibility::CONFIG) {
            if ($hasProductVisibilityResolved) {
                $delete = true;
            }
        } else {
            $update = $this->resolveStaticValues($selectedVisibility, $visibilitySettings);
        }

        $this->executeDbQuery($er, $insert, $delete, $update, $where);
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

        $isCategoryVisible = null;
        if ($category) {
            $isCategoryVisible = $this->categoryVisibilityResolver->isCategoryVisible($category);
        } else {
            $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\ProductVisibility')
                ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
                ->setToDefaultWithoutCategoryByProduct($this->insertFromSelectQueryExecutor, $product);
        }

        $repository = $this->getRepository();
        $repository->deleteByProduct($product);
        $repository->insertByProduct(
            $this->insertFromSelectQueryExecutor,
            $product,
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
            $repository->insertFromBaseTable($this->insertFromSelectQueryExecutor, $website);
            $repository->insertByCategory(
                $this->insertFromSelectQueryExecutor,
                BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
                $this->categoryVisibilityResolver->getVisibleCategoryIds(),
                $website
            );
            $repository->insertByCategory(
                $this->insertFromSelectQueryExecutor,
                BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                $this->categoryVisibilityResolver->getHiddenCategoryIds(),
                $website
            );
            $manager->commit();
        } catch (\Exception $exception) {
            $manager->rollback();
            throw $exception;
        }
    }

    /**
     * @return ProductRepository
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
