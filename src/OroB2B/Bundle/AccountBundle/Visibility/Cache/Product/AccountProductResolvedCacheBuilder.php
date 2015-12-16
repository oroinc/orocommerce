<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
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

        $em = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
        $er = $accountProductVisibilityResolved = $em
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
        $accountProductVisibilityResolved = $er->findByPrimaryKey($account, $product, $website);

        $insert = false;
        $delete = false;
        $update = [];
        $where = ['account' => $account, 'website' => $website, 'product' => $product];

        if (!$accountProductVisibilityResolved && $selectedVisibility !== AccountProductVisibility::ACCOUNT_GROUP) {
            $insert = true;
        }

        if ($selectedVisibility === AccountProductVisibility::CATEGORY) {
            $category = $this->registry
                ->getManagerForClass('OroB2BCatalogBundle:Category')
                ->getRepository('OroB2BCatalogBundle:Category')
                ->findOneByProduct($product);
            if ($category) {
                $update = [
                    'sourceProductVisibility' => $accountProductVisibility,
                    'visibility' => $this->convertVisibility(
                        $this->categoryVisibilityResolver->isCategoryVisibleForAccount($category, $account)
                    ),
                    'source' => BaseProductVisibilityResolved::SOURCE_CATEGORY,
                    'category' => $category
                ];
            } else {
                $update = $this->resolveConfigValue($accountProductVisibility);
            }
        } elseif ($selectedVisibility === AccountProductVisibility::CURRENT_PRODUCT) {
            $productVisibilityResolved = $this->getProductVisibilityResolved($product, $website);
            if ($productVisibilityResolved) {
                $update = [
                    'sourceProductVisibility' => $accountProductVisibility,
                    'visibility' => $productVisibilityResolved->getVisibility(),
                    'source' => BaseProductVisibilityResolved::SOURCE_STATIC,
                    'category' => null,
                ];
            } else {
                $update = $this->resolveConfigValue($accountProductVisibility);
            }
        } elseif ($selectedVisibility === AccountProductVisibility::ACCOUNT_GROUP) {
            if ($accountProductVisibilityResolved) {
                $delete = true;
            }
        } else {
            $update = $this->resolveStaticValues($accountProductVisibility, $selectedVisibility);
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
        // TODO: Implement updateProductResolvedVisibility() method.
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        // TODO: Implement buildCache() method.
    }

    /**
     * @param Product $product
     * @param Website $website
     * @return ProductVisibilityResolved|null
     */
    protected function getProductVisibilityResolved(Product $product, Website $website)
    {
        /** @var EntityManager $em */
        $em = $resolvedVisibility = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $resolvedVisibility = $em->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->findByPrimaryKey($product, $website);

        // entity might be inserted, but not saved yet
        if (!$resolvedVisibility) {
            foreach ($em->getUnitOfWork()->getScheduledEntityInsertions() as $entity) {
                if ($entity instanceof ProductVisibilityResolved
                    && $entity->getProduct() && $entity->getWebsite()
                    && $entity->getProduct()->getId() === $product->getId()
                    && $entity->getWebsite()->getId() === $website->getId()
                ) {
                    return $entity;
                }
            }
        }

        return $resolvedVisibility;
    }
}
