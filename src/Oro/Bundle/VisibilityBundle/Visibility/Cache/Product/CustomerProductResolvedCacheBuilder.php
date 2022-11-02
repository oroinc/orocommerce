<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerProductRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;

/**
 * The customer product visibility cache builder.
 */
class CustomerProductResolvedCacheBuilder extends AbstractProductResolvedCacheBuilder implements
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
        /** @var CustomerProductVisibility $visibilitySettings */
        $product = $visibilitySettings->getProduct();
        $scope = $visibilitySettings->getScope();

        $selectedVisibility = $visibilitySettings->getVisibility();
        /** @var VisibilityInterface $visibilitySettings */
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['scope' => $scope, 'product' => $product];

        $er = $this->doctrine->getRepository(CustomerProductVisibilityResolved::class);
        $hasCustomerProductVisibilityResolved = $er->hasEntity($where);

        if (!$hasCustomerProductVisibilityResolved
            && $selectedVisibility !== CustomerProductVisibility::CUSTOMER_GROUP) {
            $insert = true;
        }

        if ($selectedVisibility === CustomerProductVisibility::CATEGORY) {
            $category = $this->doctrine->getRepository(Category::class)->findOneByProduct($product);
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
        $category = $this->doctrine->getRepository(Category::class)->findOneByProduct($product);
        if (!$category) {
            $this->doctrine->getRepository(CustomerProductVisibility::class)
                ->setToDefaultWithoutCategoryByProduct($product);
        }

        $repository = $this->getCustomerProductRepository();
        $repository->deleteByProduct($product);
        $repository->insertByProduct($this->insertExecutor, $product, $category);

        $this->triggerProductReindexation($product, null, $scheduleReindex);
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Scope $scope = null)
    {
        $repository = $this->getCustomerProductRepository();
        $em = $this->doctrine->getManagerForClass(CustomerProductVisibilityResolved::class);
        $em->beginTransaction();
        try {
            $repository->clearTable($scope);
            $repository->insertStatic($this->insertExecutor, $scope);
            $repository->insertByCategory($this->insertExecutor, $this->scopeManager, $scope);
            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();
            throw $exception;
        }
    }

    private function getCustomerProductRepository(): CustomerProductRepository
    {
        return $this->doctrine->getRepository(CustomerProductVisibilityResolved::class);
    }

    private function prepareUpdateByCategory(
        VisibilityInterface $visibilitySettings,
        Scope $scope,
        Category $category
    ): array {
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
        $visibility = $this->doctrine->getRepository(CustomerCategoryVisibilityResolved::class)
            ->getFallbackToCustomerVisibility($category, $categoryScope, $groupScope);

        return [
            'sourceProductVisibility' => $visibilitySettings,
            'visibility' => $visibility,
            'source' => BaseProductVisibilityResolved::SOURCE_CATEGORY,
            'category' => $category
        ];
    }
}
