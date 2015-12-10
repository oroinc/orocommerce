<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;


use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupProductResolvedCacheBuilder extends AbstractCacheBuilder
{
    /**
     * @param AccountGroupProductVisibility $accountGroupProductVisibility
     */
    public function resolveVisibilitySettings($accountGroupProductVisibility)
    {
        $product = $accountGroupProductVisibility->getProduct();
        $website = $accountGroupProductVisibility->getWebsite();
        $accountGroup = $accountGroupProductVisibility->getAccountGroup();

        $selectedVisibility = $accountGroupProductVisibility->getVisibility();

        $em = $this->registry->getManagerForClass(
            'OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved'
        );
        $accountGroupProductVisibilityResolved = $em
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->findByPrimaryKey($accountGroup, $product, $website);

        if (!$accountGroupProductVisibilityResolved
            && $selectedVisibility !== AccountGroupProductVisibility::CURRENT_PRODUCT
        ) {
            $accountGroupProductVisibilityResolved = new AccountGroupProductVisibilityResolved(
                $website,
                $product,
                $accountGroup
            );
            $em->persist($accountGroupProductVisibilityResolved);
        }

        if ($selectedVisibility === AccountGroupProductVisibility::CATEGORY) {
            $category = $this->registry
                ->getManagerForClass('OroB2BCatalogBundle:Category')
                ->getRepository('OroB2BCatalogBundle:Category')
                ->findOneByProduct($product);
            if ($category) {
                $accountGroupProductVisibilityResolved->setVisibility(
                    $this->convertVisibility(
                        $this->categoryVisibilityResolver->isCategoryVisibleForAccountGroup($category, $accountGroup)
                    )
                );
                $accountGroupProductVisibilityResolved->setSourceProductVisibility($accountGroupProductVisibility);
                $accountGroupProductVisibilityResolved->setSource(BaseProductVisibilityResolved::SOURCE_CATEGORY);
                $accountGroupProductVisibilityResolved->setCategoryId($category->getId());
            } else {
                $this->resolveConfigValue($accountGroupProductVisibilityResolved, $accountGroupProductVisibility);
            }
        } elseif ($selectedVisibility === AccountGroupProductVisibility::CURRENT_PRODUCT) {
            if ($accountGroupProductVisibilityResolved) {
                $em->remove($accountGroupProductVisibilityResolved);
            }
        } else {
            $this->resolveStaticValues(
                $accountGroupProductVisibilityResolved,
                $accountGroupProductVisibility,
                $selectedVisibility
            );
        }
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
                    $this->insertFromSelectQueryExecutor,
                    BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
                    $categoriesGroupedByAccountGroup[VisibilityInterface::VISIBLE],
                    $accountGroupId
                );
                $this->getRepository()->insertByCategory(
                    $this->insertFromSelectQueryExecutor,
                    BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                    $categoriesGroupedByAccountGroup[VisibilityInterface::HIDDEN],
                    $accountGroupId
                );
            }
            $this->getRepository()->insertStatic($this->insertFromSelectQueryExecutor);
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
        return $this
            ->getManager()
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
    }

    /**
     * @return EntityManagerInterface|null
     */
    protected function getManager()
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
    }

    /**
     * @return array
     */
    protected function getCategories()
    {
        // temporary
        /** @var Category[] $categories */
        $categories = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->getCategoriesByAccountGroupProductVisibility();

        $accountGroups = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:AccountGroup')
            ->getRepository('OroB2BAccountBundle:AccountGroup')
            ->getPartialAccountGroups();

        $categoriesGrouped = [];
        foreach ($accountGroups as $accountGroup) {
            $categoriesGrouped[$accountGroup->getId()] = [VisibilityInterface::VISIBLE => [], VisibilityInterface::HIDDEN => []];
            foreach ($categories as $category) {
                if ($this->categoryVisibilityResolver->isCategoryVisibleForAccountGroup($category, $accountGroup)) {
                    $categoriesGrouped[$accountGroup->getId()][VisibilityInterface::VISIBLE][] = $category->getId();
                } else {
                    $categoriesGrouped[$accountGroup->getId()][VisibilityInterface::HIDDEN][] = $category->getId();
                }
            }
        }

        return $categoriesGrouped;
    }
}
