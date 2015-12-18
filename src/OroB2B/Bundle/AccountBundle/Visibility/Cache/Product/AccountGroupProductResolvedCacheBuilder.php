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
     * @param VisibilityInterface|AccountGroupProductVisibility $accountGroupProductVisibility
     */
    public function resolveVisibilitySettings(VisibilityInterface $accountGroupProductVisibility)
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
                $accountGroupProductVisibilityResolved->setCategory($category);
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
            $this->insertFromSelectExecutor,
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
            $websiteId = $website ? $website->getId() : null;
            $this->getRepository()->clearTable($website);
            $categoriesGrouped = $this->getCategories();
            foreach ($categoriesGrouped as $accountGroupId => $categoriesGroupedByAccountGroup) {
                $this->getRepository()->insertByCategory(
                    $this->insertFromSelectExecutor,
                    BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
                    $categoriesGroupedByAccountGroup[VisibilityInterface::VISIBLE],
                    $accountGroupId,
                    $websiteId
                );
                $this->getRepository()->insertByCategory(
                    $this->insertFromSelectExecutor,
                    BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                    $categoriesGroupedByAccountGroup[VisibilityInterface::HIDDEN],
                    $accountGroupId,
                    $websiteId
                );
            }
            $this->getRepository()->insertStatic($this->insertFromSelectExecutor, $websiteId);
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
