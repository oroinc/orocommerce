<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountGroupProductRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;

class AccountGroupProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder implements
    ProductCaseCacheBuilderInterface
{
    /**
     * @param VisibilityInterface|AccountGroupProductVisibility $visibilitySettings
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

        $em = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
        $er = $em->getRepository('OroVisibilityBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
        $hasAccountGroupProductVisibilityResolved = $er->hasEntity($where);

        if (!$hasAccountGroupProductVisibilityResolved
            && $selectedVisibility !== AccountGroupProductVisibility::CURRENT_PRODUCT
        ) {
            $insert = true;
        }

        if ($selectedVisibility === AccountGroupProductVisibility::CATEGORY) {
            $category = $this->registry
                ->getManagerForClass('OroCatalogBundle:Category')
                ->getRepository('OroCatalogBundle:Category')
                ->findOneByProduct($product);
            if ($category) {
                $categoryScope = $this->scopeManager->findOrCreate('account_group_category_visibility', $scope);
                $visibility = $this->registry
                    ->getManagerForClass(
                        'OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved'
                    )
                    ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
                    ->getFallbackToGroupVisibility($category, $categoryScope);
                $update = [
                    'sourceProductVisibility' => $visibilitySettings,
                    'visibility' => $visibility,
                    'source' => BaseProductVisibilityResolved::SOURCE_CATEGORY,
                    'category' => $category
                ];
            } else {
                // default fallback
                if ($hasAccountGroupProductVisibilityResolved) {
                    $delete = true;
                }
            }
        } elseif ($selectedVisibility === AccountGroupProductVisibility::CURRENT_PRODUCT) {
            if ($hasAccountGroupProductVisibilityResolved) {
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
        return $visibilitySettings instanceof AccountGroupProductVisibility;
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
                ->getManagerForClass('OroVisibilityBundle:Visibility\AccountGroupProductVisibility')
                ->getRepository('OroVisibilityBundle:Visibility\AccountGroupProductVisibility')
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
     * @return AccountGroupProductRepository
     */
    protected function getRepository()
    {
        return $this
            ->getManager()
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
    }

    /**
     * @return EntityManagerInterface|null
     */
    protected function getManager()
    {
        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
    }
}
