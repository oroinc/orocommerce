<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /**
     * @param VisibilityInterface|AccountProductVisibility $accountProductVisibility
     */
    public function resolveVisibilitySettings(VisibilityInterface $accountProductVisibility)
    {
        $product = $accountProductVisibility->getProduct();
        $website = $accountProductVisibility->getWebsite();
        $account = $accountProductVisibility->getAccount();

        $selectedVisibility = $accountProductVisibility->getVisibility();

        $em = $this->registry->getManagerForClass(
            'OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved'
        );
        $accountProductVisibilityResolved = $em
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->findByPrimaryKey($account, $product, $website);

        if (!$accountProductVisibilityResolved && $selectedVisibility !== AccountProductVisibility::ACCOUNT_GROUP) {
            $accountProductVisibilityResolved = new AccountProductVisibilityResolved($website, $product, $account);
            $em->persist($accountProductVisibilityResolved);
        }

        if ($selectedVisibility === AccountProductVisibility::CATEGORY) {
            $category = $this->registry
                ->getManagerForClass('OroB2BCatalogBundle:Category')
                ->getRepository('OroB2BCatalogBundle:Category')
                ->findOneByProduct($product);
            if ($category) {
                $accountProductVisibilityResolved->setVisibility(
                    $this->convertVisibility(
                        $this->categoryVisibilityResolver->isCategoryVisibleForAccount($category, $account)
                    )
                );
                $accountProductVisibilityResolved->setSourceProductVisibility($accountProductVisibility);
                $accountProductVisibilityResolved->setSource(BaseProductVisibilityResolved::SOURCE_CATEGORY);
                $accountProductVisibilityResolved->setCategory($category);
            } else {
                $this->resolveConfigValue($accountProductVisibilityResolved, $accountProductVisibility);
            }
        } elseif ($selectedVisibility === AccountProductVisibility::CURRENT_PRODUCT) {
            $productVisibilityResolved = $this->registry
                ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
                ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
                ->findByPrimaryKey($product, $website);
            if ($productVisibilityResolved) {
                $accountProductVisibilityResolved->setSource(BaseProductVisibilityResolved::SOURCE_STATIC);
                $accountProductVisibilityResolved->setCategory(null);
                $accountProductVisibilityResolved->setVisibility($productVisibilityResolved->getVisibility());
                $accountProductVisibilityResolved->setSourceProductVisibility($accountProductVisibility);
            } else {
                $this->resolveConfigValue($accountProductVisibilityResolved, $accountProductVisibility);
            }
        } elseif ($selectedVisibility === AccountProductVisibility::ACCOUNT_GROUP) {
            if ($accountProductVisibilityResolved) {
                $em->remove($accountProductVisibilityResolved);
            }
        } else {
            $this->resolveStaticValues(
                $accountProductVisibilityResolved,
                $accountProductVisibility,
                $selectedVisibility
            );
        }
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
