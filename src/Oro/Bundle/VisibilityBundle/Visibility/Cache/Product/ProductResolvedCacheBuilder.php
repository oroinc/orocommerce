<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\ProductRepository;

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

        $em = $this->registry->getManagerForClass('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $er = $em->getRepository('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $hasProductVisibilityResolved = $er->hasEntity($where);

        if (!$hasProductVisibilityResolved && $selectedVisibility !== ProductVisibility::CONFIG) {
            $insert = true;
        }

        if ($selectedVisibility === ProductVisibility::CATEGORY) {
            $category = $this->registry
                ->getManagerForClass('OroCatalogBundle:Category')
                ->getRepository('OroCatalogBundle:Category')
                ->findOneByProduct($product);
            if ($category) {
                $update = [
                    'sourceProductVisibility' => null,
                    'visibility' => $this->getCategoryVisibility($category),
                    'source' => BaseProductVisibilityResolved::SOURCE_CATEGORY,
                    'category' => $category
                ];
            } else {
                // default fallback
                if ($hasProductVisibilityResolved) {
                    $delete = true;
                }
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
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        return $visibilitySettings instanceof ProductVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function productCategoryChanged(Product $product)
    {
        $category = $this->registry
            ->getManagerForClass('OroCatalogBundle:Category')
            ->getRepository('OroCatalogBundle:Category')
            ->findOneByProduct($product);

        if ($category) {
            $visibility = $this->getCategoryVisibility($category);
        } else {
            $this->registry->getManagerForClass('OroAccountBundle:Visibility\ProductVisibility')
                ->getRepository('OroAccountBundle:Visibility\ProductVisibility')
                ->setToDefaultWithoutCategory($this->insertFromSelectQueryExecutor, $product);
            $visibility = ProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        }

        $repository = $this->getRepository();
        $repository->deleteByProduct($product);
        $repository->insertByProduct(
            $this->insertFromSelectQueryExecutor,
            $product,
            $visibility,
            $category
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        $manager = $this->getManager();
        $manager->beginTransaction();
        try {
            $repository = $this->getRepository();
            $repository->clearTable($website);
            $repository->insertStatic($this->insertFromSelectQueryExecutor, $website);
            $repository->insertByCategory($this->insertFromSelectQueryExecutor, $website);
            $manager->commit();
        } catch (\Exception $exception) {
            $manager->rollback();
            throw $exception;
        }
    }

    /**
     * @param int $visibility visible|hidden|config
     * @return array
     */
    protected function getCategoryIdsByVisibility($visibility)
    {
        return $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getCategoryIdsByNotResolvedVisibility($visibility);
    }

    /**
     * @param Category $category
     * @return int visible|hidden|config
     */
    protected function getCategoryVisibility(Category $category)
    {
        return $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getFallbackToAllVisibility($category);
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
