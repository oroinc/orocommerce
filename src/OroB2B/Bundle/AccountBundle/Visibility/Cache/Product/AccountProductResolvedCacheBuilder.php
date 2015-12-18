<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product;

use Doctrine\ORM\EntityManagerInterface;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountProductRepository;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
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
            $productVisibilityResolved = $this->getProductVisibilityResolved($product, $website);
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
            $this->getRepository()->insertForCurrentProductFallback(
                $this->insertFromSelectQueryExecutor,
                $website
            );

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
        return $this->getManager()->getRepository(
            'OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved'
        );
    }

    /**
     * @return EntityManagerInterface|null
     */
    protected function getManager()
    {
        return $this->registry->getManagerForClass(
            'OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved'
        );
    }

    /**
     * @return array
     */
    protected function getCategories()
    {
        $repo = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountProductVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility');

        $categories = $repo->getCategoriesByAccountProductVisibility();
        $accounts = $repo->getAccountsForCategoryType();

        $categoriesGrouped = [];
        foreach ($accounts as $account) {
            $categoriesGrouped[$account->getId()] = [
                VisibilityInterface::VISIBLE => [],
                VisibilityInterface::HIDDEN => [],
            ];
            foreach ($categories as $category) {
                if ($this->categoryVisibilityResolver->isCategoryVisibleForAccount($category, $account)) {
                    $categoriesGrouped[$account->getId()][VisibilityInterface::VISIBLE][] = $category->getId();
                } else {
                    $categoriesGrouped[$account->getId()][VisibilityInterface::HIDDEN][] = $category->getId();
                }
            }
        }

        return $categoriesGrouped;
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
