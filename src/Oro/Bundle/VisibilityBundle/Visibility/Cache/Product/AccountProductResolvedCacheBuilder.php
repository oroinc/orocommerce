<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountProductRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;

class AccountProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder implements
    ProductCaseCacheBuilderInterface
{
    /**
     * @param VisibilityInterface|AccountProductVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $product = $visibilitySettings->getProduct();
        $scope = $visibilitySettings->getScope();

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['scope' => $scope, 'product' => $product];

        $er = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountProductVisibilityResolved');
        $hasAccountProductVisibilityResolved = $er->hasEntity($where);

        if (!$hasAccountProductVisibilityResolved && $selectedVisibility !== AccountProductVisibility::ACCOUNT_GROUP) {
            $insert = true;
        }

        if ($selectedVisibility === AccountProductVisibility::CATEGORY) {
            $category = $this->registry
                ->getManagerForClass('OroCatalogBundle:Category')
                ->getRepository('OroCatalogBundle:Category')
                ->findOneByProduct($product);
            if ($category) {
                $categoryScope = $this->scopeManager->findOrCreate('account_category_visibility', $scope);
                $groupScope = null;
                if ($scope->getAccount()->getGroup()) {
                    $groupScope = $this->scopeManager->find(
                        'account_group_category_visibility',
                        ['accountGroup' => $scope->getAccount()->getGroup()]
                    );
                }
                $visibility = $this->registry
                    ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
                    ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
                    ->getFallbackToAccountVisibility($category, $categoryScope, $groupScope);
                $update = [
                    'sourceProductVisibility' => $visibilitySettings,
                    'visibility' => $visibility,
                    'source' => BaseProductVisibilityResolved::SOURCE_CATEGORY,
                    'category' => $category
                ];
            } else {
                // default fallback
                if ($hasAccountProductVisibilityResolved) {
                    $delete = true;
                }
            }
        } elseif ($selectedVisibility === AccountProductVisibility::CURRENT_PRODUCT) {
            $update = [
                'sourceProductVisibility' => $visibilitySettings,
                'visibility' => AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
                'source' => BaseProductVisibilityResolved::SOURCE_STATIC,
                'category' => null,
            ];
        } elseif ($selectedVisibility === AccountProductVisibility::ACCOUNT_GROUP) {
            if ($hasAccountProductVisibilityResolved) {
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
        return $visibilitySettings instanceof AccountProductVisibility;
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

        if (!$category) {
            $this->registry
                ->getManagerForClass('OroVisibilityBundle:Visibility\AccountProductVisibility')
                ->getRepository('OroVisibilityBundle:Visibility\AccountProductVisibility')
                ->setToDefaultWithoutCategoryByProduct($product);
        }

        $this->getRepository()->deleteByProduct($product);
        $this->getRepository()->insertByProduct($product, $this->insertFromSelectQueryExecutor, $category);
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
            $repository->insertStatic($this->insertFromSelectQueryExecutor, $scope);
            $repository->insertByCategory($this->insertFromSelectQueryExecutor, $scope);
            $this->getManager()->commit();
        } catch (\Exception $exception) {
            $this->getManager()->rollback();
            throw $exception;
        }
    }

    /**
     * @return AccountProductRepository
     */
    protected function getRepository()
    {
        return $this->registry
            ->getManagerForClass($this->cacheClass)
            ->getRepository($this->cacheClass);
    }

    /**
     * @return EntityManager|null
     */
    protected function getManager()
    {
        return $this->registry->getManagerForClass($this->cacheClass);
    }
}
