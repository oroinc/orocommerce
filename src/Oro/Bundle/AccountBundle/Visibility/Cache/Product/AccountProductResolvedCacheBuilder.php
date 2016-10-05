<?php

namespace Oro\Bundle\AccountBundle\Visibility\Cache\Product;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountProductRepository;
use Oro\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class AccountProductResolvedCacheBuilder extends AbstractProductResolvedCacheBuilder implements
    ProductCaseCacheBuilderInterface
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

        $er = $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
        $hasAccountProductVisibilityResolved = $er->hasEntity($where);

        if (!$hasAccountProductVisibilityResolved && $selectedVisibility !== AccountProductVisibility::ACCOUNT_GROUP) {
            $insert = true;
        }

        if ($selectedVisibility === AccountProductVisibility::CATEGORY) {
            $category = $this->registry
                ->getManagerForClass('OroCatalogBundle:Category')
                ->getRepository('OroCatalogBundle:Category')
                ->findOneByProduct($product);
            if ($category) {
                $visibility = $this->registry
                    ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
                    ->getRepository('OroAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
                    ->getFallbackToAccountVisibility($category, $account);
                $update = [
                    'sourceProductVisibility' => $visibilitySettings,
                    'visibility' => $visibility,
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
        $this->triggerProductReindexation($product, $website);
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
            ->getManagerForClass('OroCatalogBundle:Category')
            ->getRepository('OroCatalogBundle:Category')
            ->findOneByProduct($product);

        if (!$category) {
            $this->registry
                ->getManagerForClass('OroAccountBundle:Visibility\AccountProductVisibility')
                ->getRepository('OroAccountBundle:Visibility\AccountProductVisibility')
                ->setToDefaultWithoutCategoryByProduct($product);
        }

        $this->getRepository()->deleteByProduct($product);
        $this->getRepository()->insertByProduct($product, $this->insertFromSelectQueryExecutor, $category);
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        $this->getManager()->beginTransaction();
        try {
            $repository = $this->getRepository();
            $repository->clearTable($website);
            $repository->insertStatic($this->insertFromSelectQueryExecutor, $website);
            $repository->insertByCategory($this->insertFromSelectQueryExecutor, $website);
            $this->getManager()->commit();
        } catch (\Exception $exception) {
            $this->getManager()->rollback();
            throw $exception;
        }
    }

    /**
     * @return AccountProductRepository
     */
    protected function getRepository()
    {
        return $this->registry
            ->getManagerForClass($this->cacheClass)
            ->getRepository($this->cacheClass);
    }

    /**
     * @return EntityManager|null
     */
    protected function getManager()
    {
        return $this->registry->getManagerForClass($this->cacheClass);
    }
}
