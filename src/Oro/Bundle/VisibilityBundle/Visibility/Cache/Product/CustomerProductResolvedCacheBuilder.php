<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerProductRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;

/**
 * Product visibility cache builder based on customer.
 */
class CustomerProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder implements
    ProductCaseCacheBuilderInterface
{
    /**
     * @param VisibilityInterface|CustomerProductVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $product = $visibilitySettings->getProduct();
        $scope = $visibilitySettings->getScope();

        $selectedVisibility = $visibilitySettings->getVisibility();
        /** @var VisibilityInterface $visibilitySettings */
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['scope' => $scope, 'product' => $product];

        $er = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerProductVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerProductVisibilityResolved');
        $hasCustomerProductVisibilityResolved = $er->hasEntity($where);

        if (!$hasCustomerProductVisibilityResolved
            && $selectedVisibility !== CustomerProductVisibility::CUSTOMER_GROUP) {
            $insert = true;
        }

        if ($selectedVisibility === CustomerProductVisibility::CATEGORY) {
            $category = $this->registry
                ->getManagerForClass('OroCatalogBundle:Category')
                ->getRepository('OroCatalogBundle:Category')
                ->findOneByProduct($product);
            if ($category) {
                $update = $this->prepareUpdateByCategory($visibilitySettings, $scope, $category);
            } else {
                // default fallback
                if ($hasCustomerProductVisibilityResolved) {
                    $delete = true;
                }
            }
        } elseif ($selectedVisibility === CustomerProductVisibility::CURRENT_PRODUCT) {
            $update = [
                'sourceProductVisibility' => $visibilitySettings,
                'visibility' => CustomerProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
                'source' => BaseProductVisibilityResolved::SOURCE_STATIC,
                'category' => null,
            ];
        } elseif ($selectedVisibility === CustomerProductVisibility::CUSTOMER_GROUP) {
            if ($hasCustomerProductVisibilityResolved) {
                $delete = true;
            }
        } else {
            $update = $this->resolveStaticValues($selectedVisibility, $visibilitySettings);
        }

        $this->executeDbQuery($er, $insert, $delete, $update, $where);
        $this->triggerProductReindexation($product, $scope->getWebsite(), false);
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        return $visibilitySettings instanceof CustomerProductVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function productCategoryChanged(Product $product, bool $scheduleReindex)
    {
        $category = $this->registry
            ->getManagerForClass('OroCatalogBundle:Category')
            ->getRepository('OroCatalogBundle:Category')
            ->findOneByProduct($product);

        if (!$category) {
            $this->registry
                ->getManagerForClass('OroVisibilityBundle:Visibility\CustomerProductVisibility')
                ->getRepository('OroVisibilityBundle:Visibility\CustomerProductVisibility')
                ->setToDefaultWithoutCategoryByProduct($product);
        }

        $this->getRepository()->deleteByProduct($product);
        $this->getRepository()->insertByProduct($this->insertExecutor, $product, $category);

        $this->triggerProductReindexation($product, null, $scheduleReindex);
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Scope $scope = null)
    {
        $this->getManager()->beginTransaction();
        try {
            $repository = $this->getRepository();
            $repository->clearTable($scope);
            $repository->insertStatic($this->insertExecutor, $scope);
            $repository->insertByCategory($this->insertExecutor, $this->scopeManager, $scope);
            $this->getManager()->commit();
        } catch (\Exception $exception) {
            $this->getManager()->rollback();
            throw $exception;
        }
    }

    /**
     * @return CustomerProductRepository
     */
    protected function getRepository()
    {
        return $this->repository;
    }

    /**
     * @return EntityManager|null
     */
    protected function getManager()
    {
        return $this->registry->getManagerForClass($this->cacheClass);
    }

    /**
     * @param VisibilityInterface $visibilitySettings
     * @param Scope $scope
     * @param Category $category
     * @return array
     */
    protected function prepareUpdateByCategory(
        VisibilityInterface $visibilitySettings,
        Scope $scope,
        Category $category
    ) {
        $categoryScope = $this->scopeManager->findOrCreate('customer_category_visibility', $scope);
        $groupScope = null;
        /** @noinspection PhpUndefinedMethodInspection - field added through entity extend */
        $group = $scope->getCustomer()->getGroup();
        if ($group) {
            /** @noinspection PhpUndefinedMethodInspection - field added through entity extend */
            $groupScope = $this->scopeManager->find(
                'customer_group_category_visibility',
                ['customerGroup' => $group]
            );
        }
        $visibility = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved')
            ->getFallbackToCustomerVisibility($category, $categoryScope, $groupScope);
        $update = [
            'sourceProductVisibility' => $visibilitySettings,
            'visibility' => $visibility,
            'source' => BaseProductVisibilityResolved::SOURCE_CATEGORY,
            'category' => $category
        ];
        return $update;
    }
}
