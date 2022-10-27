<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\ProductRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;

/**
 * The product visibility cache builder.
 */
class ProductResolvedCacheBuilder extends AbstractProductResolvedCacheBuilder implements
    ProductCaseCacheBuilderInterface
{
    private ScopeManager $scopeManager;
    private InsertFromSelectQueryExecutor $insertExecutor;

    public function __construct(
        ManagerRegistry $doctrine,
        ProductReindexManager $productReindexManager,
        ScopeManager $scopeManager,
        InsertFromSelectQueryExecutor $insertExecutor
    ) {
        parent::__construct($doctrine, $productReindexManager);
        $this->scopeManager = $scopeManager;
        $this->insertExecutor = $insertExecutor;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        /** @var ProductVisibility $visibilitySettings */
        $scope = $visibilitySettings->getScope();
        $product = $visibilitySettings->getProduct();

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['scope' => $scope, 'product' => $product];

        $er = $this->doctrine->getRepository(ProductVisibilityResolved::class);
        $hasProductVisibilityResolved = $er->hasEntity($where);

        if (!$hasProductVisibilityResolved && $selectedVisibility !== ProductVisibility::CONFIG) {
            $insert = true;
        }

        if ($selectedVisibility === ProductVisibility::CATEGORY) {
            $category = $this->doctrine->getRepository(Category::class)->findOneByProduct($product);
            if ($category) {
                $update = [
                    'sourceProductVisibility' => null,
                    'visibility' => $this->getCategoryVisibility($category),
                    'source' => BaseProductVisibilityResolved::SOURCE_CATEGORY,
                    'category' => $category
                ];
            } elseif ($hasProductVisibilityResolved) {
                // default fallback
                $delete = true;
            }
        } elseif ($selectedVisibility === ProductVisibility::CONFIG) {
            if ($hasProductVisibilityResolved) {
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
        return $visibilitySettings instanceof ProductVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function productCategoryChanged(Product $product, bool $scheduleReindex)
    {
        $category = $this->doctrine->getRepository(Category::class)->findOneByProduct($product);
        if ($category) {
            $visibility = $this->getCategoryVisibility($category);
        } else {
            $scopes = $this->scopeManager->findRelatedScopes(ProductVisibility::VISIBILITY_TYPE);
            foreach ($scopes as $scope) {
                $this->doctrine->getRepository(ProductVisibility::class)
                    ->setToDefaultWithoutCategory($this->insertExecutor, $scope, $product);
            }
            $visibility = ProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        }

        $repository = $this->getProductRepository();
        $repository->deleteByProduct($product);
        $scopes = $this->scopeManager->findRelatedScopes(ProductVisibility::VISIBILITY_TYPE);
        foreach ($scopes as $scope) {
            $repository->insertByProduct(
                $this->insertExecutor,
                $product,
                $visibility,
                $scope,
                $category
            );
        }

        $this->triggerProductReindexation($product, null, $scheduleReindex);
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Scope $scope = null)
    {
        $repository = $this->getProductRepository();
        $em = $this->doctrine->getManagerForClass(ProductVisibilityResolved::class);
        $em->beginTransaction();
        try {
            $repository->clearTable($scope);
            $repository->insertStatic($this->insertExecutor, $scope);
            if ($scope) {
                $categoryScope = $this->scopeManager->findOrCreate(CategoryVisibility::VISIBILITY_TYPE, $scope);
                $repository->insertByCategory($this->insertExecutor, $scope, $categoryScope);
            } else {
                $scopes = $this->scopeManager->findRelatedScopes(ProductVisibility::VISIBILITY_TYPE);
                foreach ($scopes as $scope) {
                    $categoryScope = $this->scopeManager->findOrCreate(CategoryVisibility::VISIBILITY_TYPE, $scope);
                    $repository->insertByCategory($this->insertExecutor, $scope, $categoryScope);
                }
            }
            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();
            throw $exception;
        }
    }

    private function getCategoryVisibility(Category $category): int
    {
        return $this->doctrine->getRepository(CategoryVisibilityResolved::class)
            ->getFallbackToAllVisibility($category);
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->doctrine->getRepository(ProductVisibilityResolved::class);
    }
}
