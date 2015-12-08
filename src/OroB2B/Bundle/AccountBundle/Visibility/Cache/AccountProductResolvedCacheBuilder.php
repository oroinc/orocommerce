<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityResolver;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountProductResolvedCacheBuilder implements CacheBuilderInterface
{
    const VISIBLE = 'visible';
    const HIDDEN = 'hidden';

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectExecutor;

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var string
     */
    protected $cacheClass;

    /**
     * ProductResolvedCacheBuilder constructor.
     * @param RegistryInterface $doctrine
     * @param InsertFromSelectQueryExecutor $executor
     * @param CategoryVisibilityResolver $categoryVisibilityResolver
     */
    public function __construct(
        RegistryInterface $doctrine,
        InsertFromSelectQueryExecutor $executor,
        CategoryVisibilityResolver $categoryVisibilityResolver
    ) {
        $this->doctrine = $doctrine;
        $this->insertFromSelectExecutor = $executor;
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveVisibilitySettings($visibilitySettings)
    {
        // TODO: Implement resolveVisibilitySettings() method.
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported($visibilitySettings)
    {
        return $visibilitySettings instanceof AccountProductVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function updateResolvedVisibilityByCategory(Category $category)
    {
        // TODO: Implement updateResolvedVisibilityByCategory() method.
    }

    /**
     * {@inheritdoc}
     */
    public function updateProductResolvedVisibility(Product $product)
    {
        // TODO: Implement updateProductResolvedVisibility() method.
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        $this->getManager()->beginTransaction();
        try {
            $this->getRepository()->clearTable();

            $categoriesGrouped = $this->getCategories();
            foreach ($categoriesGrouped as $accountId => $categoriesGroupedByAccount) {
                $this->getRepository()->insertByCategory(
                    $this->insertFromSelectExecutor,
                    BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
                    $categoriesGroupedByAccount[self::VISIBLE],
                    $accountId
                );
                $this->getRepository()->insertByCategory(
                    $this->insertFromSelectExecutor,
                    BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                    $categoriesGroupedByAccount[self::HIDDEN],
                    $accountId
                );
            }
            $this->getRepository()->deleteByVisibility(AccountProductVisibility::ACCOUNT_GROUP);

            $this->getRepository()->updateFromBaseTableForCurrentProduct(
                BaseProductVisibilityResolved::VISIBILITY_VISIBLE
            );

            $this->getRepository()->updateFromBaseTableForCurrentProduct(
                BaseProductVisibilityResolved::VISIBILITY_HIDDEN
            );

            $this->getRepository()->updateFromBaseTable(
                BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
                AccountProductVisibility::VISIBLE
            );
            $this->getRepository()->updateFromBaseTable(
                BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                AccountProductVisibility::HIDDEN
            );
            $this->getManager()->commit();
        } catch (\Exception $exception) {
            $this->getManager()->rollback();
            throw $exception;
        }
    }


    /**
     * @return AccountProductVisibilityResolvedRepository
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
        return $this->doctrine->getManagerForClass($this->cacheClass);
    }

    /**
     * @return array
     */
    protected function getCategories()
    {
        // temporary
        /** @var Category[] $categories */
        $categories = $this->doctrine
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getPartialCategories();

        $accounts = $this->doctrine
            ->getManagerForClass('OroB2BAccountBundle:Account')
            ->getRepository('OroB2BAccountBundle:Account')
            ->getPartialAccounts();

        $categoriesGrouped = [];
        foreach ($accounts as $account) {
            $categoriesGrouped[$account->getId()] = [self::VISIBLE => [], self::HIDDEN => []];
            foreach ($categories as $category) {
                if ($this->categoryVisibilityResolver->isCategoryVisibleForAccount($category, $account)) {
                    $categoriesGrouped[$account->getId()][self::VISIBLE][] = $category->getId();
                } else {
                    $categoriesGrouped[$account->getId()][self::HIDDEN][] = $category->getId();
                }
            }
        }

        return $categoriesGrouped;
    }

    /**
     * @param string $cacheClass
     */
    public function setCacheClass($cacheClass)
    {
        $this->cacheClass = $cacheClass;
    }
}
