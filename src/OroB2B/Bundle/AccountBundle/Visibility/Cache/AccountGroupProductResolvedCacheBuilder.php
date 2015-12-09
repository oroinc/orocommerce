<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityResolver;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupProductResolvedCacheBuilder implements CacheBuilderInterface
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
     * @param string $cacheClass
     */
    public function setCacheClass($cacheClass)
    {
        $this->cacheClass = $cacheClass;
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
        return $visibilitySettings instanceof AccountGroupProductVisibility;
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
            foreach ($categoriesGrouped as $accountGroupId => $categoriesGroupedByAccountGroup) {
                $this->getRepository()->insertByCategory(
                    $this->insertFromSelectExecutor,
                    BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
                    $categoriesGroupedByAccountGroup[self::VISIBLE],
                    $accountGroupId
                );
                $this->getRepository()->insertByCategory(
                    $this->insertFromSelectExecutor,
                    BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                    $categoriesGroupedByAccountGroup[self::HIDDEN],
                    $accountGroupId
                );
            }
            $this->getRepository()->insertStatic($this->insertFromSelectExecutor);
            $this->getManager()->commit();
        } catch (\Exception $exception) {
            $this->getManager()->rollback();
            throw $exception;
        }
    }


    /**
     * @return AccountGroupProductVisibilityResolvedRepository
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
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->getCategoriesByAccountGroupProductVisibility();

        $accountGroups = $this->doctrine
            ->getManagerForClass('OroB2BAccountBundle:AccountGroup')
            ->getRepository('OroB2BAccountBundle:AccountGroup')
            ->getPartialAccountGroups();

        $categoriesGrouped = [];
        foreach ($accountGroups as $accountGroup) {
            $categoriesGrouped[$accountGroup->getId()] = [self::VISIBLE => [], self::HIDDEN => []];
            foreach ($categories as $category) {
                if ($this->categoryVisibilityResolver->isCategoryVisibleForAccountGroup($category, $accountGroup)) {
                    $categoriesGrouped[$accountGroup->getId()][self::VISIBLE][] = $category->getId();
                } else {
                    $categoriesGrouped[$accountGroup->getId()][self::HIDDEN][] = $category->getId();
                }
            }
        }

        return $categoriesGrouped;
    }
}
