<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupProductResolvedCacheBuilder extends AbstractCacheBuilder implements CacheBuilderInterface
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
            ->findOneBy(['product' => $product, 'website' => $website, 'accountGroup' => $accountGroup]);

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

        if ($selectedVisibility == AccountGroupProductVisibility::CATEGORY) {
            $category = $this->registry
                ->getManagerForClass('OroB2BCatalogBundle:Category')
                ->getRepository('OroB2BCatalogBundle:Category')
                ->findOneByProduct($product);
            $accountGroupProductVisibilityResolved->setVisibility(
                $this->categoryVisibilityResolver->getCategoryVisibilityForAccountGroup($category, $accountGroup)
            );
            $accountGroupProductVisibilityResolved->setSourceProductVisibility($accountGroupProductVisibility);
            $accountGroupProductVisibilityResolved->setSource(AccountGroupProductVisibilityResolved::SOURCE_CATEGORY);
            $accountGroupProductVisibilityResolved->setCategoryId($category->getId());
        } elseif ($selectedVisibility == AccountGroupProductVisibility::CURRENT_PRODUCT
            && $accountGroupProductVisibilityResolved
        ) {
            $em->remove($accountGroupProductVisibilityResolved);
        } else {
            $this->resolveStaticValues(
                $accountGroupProductVisibility,
                $accountGroupProductVisibilityResolved,
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
        // TODO: Implement buildCache() method.
    }
}
