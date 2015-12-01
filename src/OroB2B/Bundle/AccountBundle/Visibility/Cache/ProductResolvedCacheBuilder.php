<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQuery;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityResolver;

class ProductResolvedCacheBuilder extends AbstractCacheBuilder
{
    /**
     * @var InsertFromSelectQuery
     */
    protected $insertFromSelectHelper;

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var string
     */
    protected $cacheClass;

    /**
     * @var CategoryVisibilityResolver
     */
    protected $categoryVisibilityResolver;

    /**
     * ProductResolvedCacheBuilder constructor.
     * @param RegistryInterface $doctrine
     * @param InsertFromSelectQuery $helper
     * @param CategoryVisibilityResolver $categoryVisibilityResolver
     * @param string $cacheClass
     */
    public function __construct(
        RegistryInterface $doctrine,
        InsertFromSelectQuery $helper,
        CategoryVisibilityResolver $categoryVisibilityResolver,
        $cacheClass
    ) {
        $this->doctrine = $doctrine;
        $this->insertFromSelectHelper = $helper;
        $this->cacheClass = $cacheClass;
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
    }


    /**
     * {@inheritdoc}
     */
    public function resolveVisibilitySettings($visibilitySettings)
    {
        // TODO: Implement resolveVisibilitySettings() method.
    }

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
        //todo transaction
        $this->clearBeforeBuild();

        $categoriesGrouped = $this->getCategories();
        $this->insertByCategory(BaseProductVisibilityResolved::VISIBILITY_VISIBLE, $categoriesGrouped['visible']);
        $this->insertByCategory(BaseProductVisibilityResolved::VISIBILITY_HIDDEN, $categoriesGrouped['hidden']);

        $this->deleteByVisibility(ProductVisibility::CONFIG);
        $this->updateFromBaseTable(BaseProductVisibilityResolved::VISIBILITY_VISIBLE, ProductVisibility::VISIBLE);
        $this->updateFromBaseTable(BaseProductVisibilityResolved::VISIBILITY_HIDDEN, ProductVisibility::HIDDEN);
    }

    /**
     * @return array
     */
    protected function getCategories()
    {
        /** @var Category[] $categories */
        $categories = $this->doctrine->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select('partial category.{id}')
            ->getQuery()
            ->getResult();

        $categoriesGrouped = ['visible' => [], 'hidden' => []];

        foreach ($categories as $category) {
            if ($this->categoryVisibilityResolver->isCategoryVisible($category)) {
                $categoriesGrouped['visible'][] = $category->getId();
            } else {
                $categoriesGrouped['hidden'][] = $category->getId();
            }
        }

        return $categoriesGrouped;
    }

    /**
     * @param $cacheVisibility
     * @param $categories
     */
    protected function insertByCategory($cacheVisibility, $categories)
    {
        $queryBuilder = $this->doctrine->getEntityManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select([
                'website.id as websiteId',
                'product.id as productId',
                (string)$cacheVisibility,
                (string)BaseProductVisibilityResolved::SOURCE_CATEGORY,
                'category.id as categoryId'
            ])
            ->innerJoin('OroB2BProductBundle:Product', 'product', Join::WITH, 'product MEMBER OF category.products')
            ->innerJoin('OroB2BWebsiteBundle:Website', 'website')
            ->where('category.id in (:ids)')
            ->setParameter('ids', $categories)
        ;

        $this->insertFromSelectHelper->execute($this->cacheClass, [
            'website', 'product', 'visibility', 'source', 'categoryId'
        ], $queryBuilder);
    }

    /**
     * @param string $baseVisibility
     * @param string $cacheVisibility
     */
    protected function updateFromBaseTable($cacheVisibility, $baseVisibility)
    {
        $qb = $this->doctrine->getEntityManager()->createQueryBuilder();

        $qb->update('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved', 'pvr')
            ->set('pvr.visibility', $cacheVisibility)
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->in(
                        'IDENTITY(pvr.product)',
                        $this->doctrine->getEntityManager()->createQueryBuilder()
                            ->select('IDENTITY(pv_1.product)')
                            ->from('OroB2BAccountBundle:Visibility\ProductVisibility', 'pv_1')
                            ->where('IDENTITY(pv_1.product) = IDENTITY(pvr.product)')
                            ->andWhere('IDENTITY(pv_1.website) = IDENTITY(pvr.website)')
                            ->andWhere('pv_1.visibility = :visibility')
                            ->setParameter('visibility', $baseVisibility)
                            ->getDQL()
                    ),
                    $qb->expr()->in(
                        'IDENTITY(pvr.website)',
                        $this->doctrine->getEntityManager()->createQueryBuilder()
                            ->select('IDENTITY(pv_2.website)')
                            ->from('OroB2BAccountBundle:Visibility\ProductVisibility', 'pv_2')
                            ->where('IDENTITY(pv_2.product) = IDENTITY(pvr.product)')
                            ->andWhere('IDENTITY(pv_2.website) = IDENTITY(pvr.website)')
                            ->getDQL()
                    )
                )
            )
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param $visibility
     */
    protected function deleteByVisibility($visibility)
    {
        $qb = $this->doctrine->getEntityManager()->createQueryBuilder();

        $qb->delete('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved', 'pvr')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->in(
                        'IDENTITY(pvr.product)',
                        $this->doctrine->getEntityManager()->createQueryBuilder()
                            ->select('IDENTITY(pv_1.product)')
                            ->from('OroB2BAccountBundle:Visibility\ProductVisibility', 'pv_1')
                            ->where('IDENTITY(pv_1.product) = IDENTITY(pvr.product)')
                            ->andWhere('IDENTITY(pv_1.website) = IDENTITY(pvr.website)')
                            ->andWhere('pv_1.visibility = :visibility')
                            ->setParameter('visibility', $visibility)
                            ->getDQL()
                    ),
                    $qb->expr()->in(
                        'IDENTITY(pvr.website)',
                        $this->doctrine->getEntityManager()->createQueryBuilder()
                            ->select('IDENTITY(pv_2.website)')
                            ->from('OroB2BAccountBundle:Visibility\ProductVisibility', 'pv_2')
                            ->where('IDENTITY(pv_2.product) = IDENTITY(pvr.product)')
                            ->andWhere('IDENTITY(pv_2.website) = IDENTITY(pvr.website)')
                            ->getDQL()
                    )
                )
            )
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        return $this->doctrine
            ->getManagerForClass($this->cacheClass)
            ->getRepository($this->cacheClass);
    }
}
