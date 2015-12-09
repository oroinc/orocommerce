<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class ProductResolvedCacheBuilder extends AbstractCacheBuilder
{
    /**
     * @param ProductVisibility $productVisibility
     */
    public function resolveVisibilitySettings($productVisibility)
    {
        $product = $productVisibility->getProduct();
        $website = $productVisibility->getWebsite();

        $selectedVisibility = $productVisibility->getVisibility();

        $em = $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $productVisibilityResolved = $em
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->findByPrimaryKey($product, $website);

        if (!$productVisibilityResolved && $selectedVisibility !== ProductVisibility::CONFIG) {
            $productVisibilityResolved = new ProductVisibilityResolved($website, $product);
            $em->persist($productVisibilityResolved);
        }

        if ($selectedVisibility === ProductVisibility::CATEGORY) {
            $category = $this->registry
                ->getManagerForClass('OroB2BCatalogBundle:Category')
                ->getRepository('OroB2BCatalogBundle:Category')
                ->findOneByProduct($product);

            if ($category) {
                $productVisibilityResolved->setSourceProductVisibility(null);
                $productVisibilityResolved->setVisibility(
                    $this->categoryVisibilityResolver->getCategoryVisibility($category)
                );
                $productVisibilityResolved->setSource(BaseProductVisibilityResolved::SOURCE_CATEGORY);
                $productVisibilityResolved->setCategoryId($category->getId());
            } else {
                $this->resolveConfigValue($productVisibility, $productVisibilityResolved);
            }
        } elseif ($selectedVisibility === ProductVisibility::CONFIG) {
            if ($productVisibilityResolved) {
                $em->remove($productVisibilityResolved);
            }
        } else {
            $this->resolveStaticValues($productVisibility, $productVisibilityResolved, $selectedVisibility);
        }

        // set calculated visibility to account resolved values
        if ($productVisibilityResolved && $selectedVisibility !== ProductVisibility::CONFIG) {
            $visibility = $productVisibilityResolved->getVisibility();
        } else {
            $visibility = $this->getVisibilityFromConfig();
        }
        $this->registry->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->updateCurrentProductRelatedEntities($website, $product, $visibility);
    }

    /**
     * {@inheritdoc}
     */
    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported($visibilitySettings)
    {
        return $visibilitySettings instanceof ProductVisibility;
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
