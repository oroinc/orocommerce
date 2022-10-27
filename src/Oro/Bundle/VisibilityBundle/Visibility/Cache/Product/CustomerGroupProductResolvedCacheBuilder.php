<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerGroupProductRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;

/**
 * The customer group visibility cache builder.
 */
class CustomerGroupProductResolvedCacheBuilder extends AbstractProductResolvedCacheBuilder implements
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
        /** @var CustomerGroupProductVisibility $visibilitySettings */
        $product = $visibilitySettings->getProduct();
        $scope = $visibilitySettings->getScope();

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['scope' => $scope, 'product' => $product];

        $er = $this->doctrine->getRepository(CustomerGroupProductVisibilityResolved::class);
        $hasCustomerGroupProductVisibilityResolved = $er->hasEntity($where);

        if (!$hasCustomerGroupProductVisibilityResolved
            && $selectedVisibility !== CustomerGroupProductVisibility::CURRENT_PRODUCT
        ) {
            $insert = true;
        }

        if ($selectedVisibility === CustomerGroupProductVisibility::CATEGORY) {
            $category = $this->doctrine->getRepository(Category::class)->findOneByProduct($product);
            if ($category) {
                $categoryScope = $this->scopeManager->findOrCreate('customer_group_category_visibility', $scope);

                $visibility = $this->doctrine->getRepository(CustomerGroupCategoryVisibilityResolved::class)
                    ->getFallbackToGroupVisibility($category, $categoryScope);

                $update = [
                    'sourceProductVisibility' => $visibilitySettings,
                    'visibility' => $visibility,
                    'source' => BaseProductVisibilityResolved::SOURCE_CATEGORY,
                    'category' => $category
                ];
            } else {
                // default fallback
                if ($hasCustomerGroupProductVisibilityResolved) {
                    $delete = true;
                }
            }
        } elseif ($selectedVisibility === CustomerGroupProductVisibility::CURRENT_PRODUCT) {
            if ($hasCustomerGroupProductVisibilityResolved) {
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
        return $visibilitySettings instanceof CustomerGroupProductVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function productCategoryChanged(Product $product, bool $scheduleReindex)
    {
        $category = $this->doctrine->getRepository(Category::class)->findOneByProduct($product);
        if (!$category) {
            $this->doctrine->getRepository(CustomerGroupProductVisibility::class)
                ->setToDefaultWithoutCategoryByProduct($product);
        }

        $repository = $this->getCustomerGroupProductRepository();
        $repository->deleteByProduct($product);
        $repository->insertByProduct($this->insertExecutor, $product, $category);

        $this->triggerProductReindexation($product, null, $scheduleReindex);
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Scope $scope = null)
    {
        $repository = $this->getCustomerGroupProductRepository();
        $em = $this->doctrine->getManagerForClass(CustomerGroupProductVisibilityResolved::class);
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

    private function getCustomerGroupProductRepository(): CustomerGroupProductRepository
    {
        return $this->doctrine->getRepository(CustomerGroupProductVisibilityResolved::class);
    }
}
