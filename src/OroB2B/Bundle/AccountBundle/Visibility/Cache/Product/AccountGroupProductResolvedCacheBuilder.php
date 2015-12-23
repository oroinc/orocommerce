<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product;

use Doctrine\ORM\EntityManagerInterface;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupProductRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /**
     * @param VisibilityInterface|AccountGroupProductVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $product = $visibilitySettings->getProduct();
        $website = $visibilitySettings->getWebsite();
        $accountGroup = $visibilitySettings->getAccountGroup();

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['accountGroup' => $accountGroup, 'website' => $website, 'product' => $product];

        $em = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
        $er = $em->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
        $hasAccountGroupProductVisibilityResolved = $er->hasEntity($where);

        if (!$hasAccountGroupProductVisibilityResolved
            && $selectedVisibility !== AccountGroupProductVisibility::CURRENT_PRODUCT
        ) {
            $insert = true;
        }

        if ($selectedVisibility === AccountGroupProductVisibility::CATEGORY) {
            $category = $this->registry
                ->getManagerForClass('OroB2BCatalogBundle:Category')
                ->getRepository('OroB2BCatalogBundle:Category')
                ->findOneByProduct($product);
            if ($category) {
                $update = [
                    'sourceProductVisibility' => $visibilitySettings,
                    'visibility' => $this->convertVisibility(
                        $this->categoryVisibilityResolver->isCategoryVisibleForAccountGroup($category, $accountGroup)
                    ),
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
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->findOneByProduct($product);
        $isCategoryVisible = null;
        if ($category) {
            $isCategoryVisible = $this->categoryVisibilityResolver->isCategoryVisible($category);
        }
        $this->getRepository()->deleteByProduct($product);
        $this->getRepository()->insertByProduct(
            $product,
            $this->insertFromSelectQueryExecutor,
            $isCategoryVisible,
            $category
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        $this->getManager()->beginTransaction();
        try {
            $this->getRepository()->clearTable($website);
            $websiteId = $website ? $website->getId() : null;

            $categoriesGrouped = $this->getCategories();
            foreach ($categoriesGrouped as $accountGroupId => $categoriesGroupedByAccountGroup) {
                $this->getRepository()->insertByCategory(
                    $this->insertFromSelectQueryExecutor,
                    BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
                    $categoriesGroupedByAccountGroup[VisibilityInterface::VISIBLE],
                    $accountGroupId,
                    $websiteId
                );
                $this->getRepository()->insertByCategory(
                    $this->insertFromSelectQueryExecutor,
                    BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                    $categoriesGroupedByAccountGroup[VisibilityInterface::HIDDEN],
                    $accountGroupId,
                    $websiteId
                );
            }
            $this->getRepository()->insertStatic($this->insertFromSelectQueryExecutor, $websiteId);
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
        $repo = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility');

        $categories = $repo->getCategoryIdsByAccountGroupProductVisibility();
        $accountGroups = $repo->getAccountGroupsWithCategoryVisibiliy();

        $categoriesGrouped = [];

        foreach ($accountGroups as $accountGroup) {
            $categoriesGrouped[$accountGroup->getId()] = [
                VisibilityInterface::VISIBLE => array_intersect(
                    $this->categoryVisibilityResolver->getVisibleCategoryIdsForAccountGroup($accountGroup),
                    $categories
                ),
                VisibilityInterface::HIDDEN => array_intersect(
                    $this->categoryVisibilityResolver->getHiddenCategoryIdsForAccountGroup($accountGroup),
                    $categories
                ),
            ];

        }

        return $categoriesGrouped;
    }
}
