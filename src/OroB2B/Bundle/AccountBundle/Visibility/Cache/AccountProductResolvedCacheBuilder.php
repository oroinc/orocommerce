<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use Doctrine\ORM\EntityManagerInterface;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountProductResolvedCacheBuilder extends AbstractCacheBuilder
{
    /**
     * @param AccountProductVisibility $accountProductVisibility
     */
    public function resolveVisibilitySettings($accountProductVisibility)
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
                $accountProductVisibilityResolved->setCategoryId($category->getId());
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
                $accountProductVisibilityResolved->setCategoryId(null);
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
    public function isVisibilitySettingsSupported($visibilitySettings)
    {
        return $visibilitySettings instanceof AccountProductVisibility;
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
        $this->getManager()->beginTransaction();
        try {
            $this->getRepository()->clearTable();

            $categoriesGrouped = $this->getCategories();
            foreach ($categoriesGrouped as $accountId => $categoriesGroupedByAccount) {
                $this->getRepository()->insertByCategory(
                    $this->insertFromSelectQueryExecutor,
                    BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
                    $categoriesGroupedByAccount[VisibilityInterface::VISIBLE],
                    $accountId
                );
                $this->getRepository()->insertByCategory(
                    $this->insertFromSelectQueryExecutor,
                    BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                    $categoriesGroupedByAccount[VisibilityInterface::HIDDEN],
                    $accountId
                );
            }
            $this->getRepository()->insertStatic($this->insertFromSelectQueryExecutor);
            $this->getRepository()->insertForCurrentProductFallback(
                $this->insertFromSelectQueryExecutor,
                $this->getVisibilityFromConfig()
            );

            $this->getManager()->commit();
        } catch (\Exception $exception) {
            $this->getManager()->rollback();
            throw $exception;
        }
    }

    /**
     * @return AccountProductVisibilityResolvedRepository
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
        /** @var Category[] $categories */
        $categories = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountProductVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility')
            ->getCategoriesByAccountProductVisibility();

        $accounts = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Account')
            ->getRepository('OroB2BAccountBundle:Account')
            ->getPartialAccounts();

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
}
