<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\ProductRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;

class ProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder implements ProductCaseCacheBuilderInterface
{
    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @param VisibilityInterface|ProductVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $scope = $visibilitySettings->getScope();
        $product = $visibilitySettings->getProduct();

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['scope' => $scope, 'product' => $product];

        $em = $this->registry->getManagerForClass('OroVisibilityBundle:VisibilityResolved\ProductVisibilityResolved');
        $er = $em->getRepository('OroVisibilityBundle:VisibilityResolved\ProductVisibilityResolved');
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
            $scopes = $this->scopeManager->findRelatedScopes(ProductVisibility::VISIBILITY_TYPE);
            foreach ($scopes as $scope) {
                $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\ProductVisibility')
                    ->getRepository('OroVisibilityBundle:Visibility\ProductVisibility')
                    ->setToDefaultWithoutCategory($scope, $product);
            }
            $visibility = ProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        }

        $repository = $this->getRepository();
        $repository->deleteByProduct($product);
        $scopes = $this->scopeManager->findRelatedScopes(ProductVisibility::VISIBILITY_TYPE);
        foreach ($scopes as $scope) {
            $repository->insertByProduct(
                $product,
                $visibility,
                $scope,
                $category
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Scope $scope = null)
    {
        $manager = $this->getManager();
        $manager->beginTransaction();
        try {
            $repository = $this->getRepository();
            $repository->clearTable($scope);
            $repository->insertStatic($scope);
            if ($scope) {
                $categoryScope = $this->scopeManager->findOrCreate(CategoryVisibility::VISIBILITY_TYPE, $scope);
                $repository->insertByCategory($scope, $categoryScope);
            } else {
                $scopes = $this->scopeManager->findRelatedScopes(ProductVisibility::VISIBILITY_TYPE);
                foreach ($scopes as $scope) {
                    $categoryScope = $this->scopeManager->findOrCreate(CategoryVisibility::VISIBILITY_TYPE, $scope);
                    $repository->insertByCategory($scope, $categoryScope);
                }
            }
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
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getCategoryIdsByNotResolvedVisibility($visibility);
    }

    /**
     * @param Category $category
     * @return int visible|hidden|config
     */
    protected function getCategoryVisibility(Category $category)
    {
        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getFallbackToAllVisibility($category);
    }

    /**
     * @return ProductRepository
     */
    protected function getRepository()
    {
        return $this->repositoryHolder->getRepository();
    }

    /**
     * @return EntityManagerInterface|null
     */
    protected function getManager()
    {
        return $this->registry->getManagerForClass($this->cacheClass);
    }

    /**
     * @param ScopeManager $scopeManager
     */
    public function setScopeManager($scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }
}
