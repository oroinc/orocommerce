<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /**
     * @param VisibilityInterface|AccountProductVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $product = $visibilitySettings->getProduct();
        $website = $visibilitySettings->getWebsite();
        $account = $visibilitySettings->getAccount();

        $selectedVisibility = $visibilitySettings->getVisibility();
        $visibilitySettings = $this->refreshEntity($visibilitySettings);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['account' => $account, 'website' => $website, 'product' => $product];

        $em = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
        $er = $em->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
        $hasAccountProductVisibilityResolved = $er->hasEntity($where);

        if (!$hasAccountProductVisibilityResolved && $selectedVisibility !== AccountProductVisibility::ACCOUNT_GROUP) {
            $insert = true;
        }

        if ($selectedVisibility === AccountProductVisibility::CATEGORY) {
            $category = $this->registry
                ->getManagerForClass('OroB2BCatalogBundle:Category')
                ->getRepository('OroB2BCatalogBundle:Category')
                ->findOneByProduct($product);
            if ($category) {
                $update = [
                    'sourceProductVisibility' => $visibilitySettings,
                    'visibility' => $this->convertVisibility(
                        $this->categoryVisibilityResolver->isCategoryVisibleForAccount($category, $account)
                    ),
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
     * @return AccountProductVisibilityResolvedRepository
     */
    protected function getRepository()
    {
        return $this->registry
            ->getManagerForClass($this->cacheClass)
            ->getRepository($this->cacheClass);
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
            foreach ($categoriesGrouped as $accountId => $categoriesGroupedByAccount) {
                $this->getRepository()->insertByCategory(
                    $this->insertFromSelectQueryExecutor,
                    BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
                    $categoriesGroupedByAccount[VisibilityInterface::VISIBLE],
                    $accountId,
                    $websiteId
                );
                $this->getRepository()->insertByCategory(
                    $this->insertFromSelectQueryExecutor,
                    BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                    $categoriesGroupedByAccount[VisibilityInterface::HIDDEN],
                    $accountId,
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
}
