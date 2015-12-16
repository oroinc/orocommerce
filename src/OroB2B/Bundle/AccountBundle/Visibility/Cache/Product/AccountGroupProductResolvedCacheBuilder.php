<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product;

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

        $em = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
        $er = $em->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
        $accountGroupProductVisibilityResolved = $er->findByPrimaryKey($accountGroup, $product, $website);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['accountGroup' => $accountGroup, 'website' => $website, 'product' => $product];

        if (!$accountGroupProductVisibilityResolved
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
                    'sourceProductVisibility' => $accountGroupProductVisibility,
                    'visibility' => $this->convertVisibility(
                        $this->categoryVisibilityResolver->isCategoryVisibleForAccountGroup($category, $accountGroup)
                    ),
                    'source' => BaseProductVisibilityResolved::SOURCE_CATEGORY,
                    'category' => $category
                ];
            } else {
                $update = $this->resolveConfigValue($accountGroupProductVisibility);
            }
        } elseif ($selectedVisibility === AccountGroupProductVisibility::CURRENT_PRODUCT) {
            if ($accountGroupProductVisibilityResolved) {
                $delete = true;
            }
        } else {
            $update = $this->resolveStaticValues($accountGroupProductVisibility, $selectedVisibility);
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
