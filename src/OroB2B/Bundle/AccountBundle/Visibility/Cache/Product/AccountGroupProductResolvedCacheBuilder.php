<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
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
        // TODO: Implement buildCache() method.
    }

    /**
     * @return AccountGroupProductVisibilityResolvedRepository
     */
    protected function getRepository()
    {
        return $this->registry
            ->getManagerForClass($this->cacheClass)
            ->getRepository($this->cacheClass);
    }
}
