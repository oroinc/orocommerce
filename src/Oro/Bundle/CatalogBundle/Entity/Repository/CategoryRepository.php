<?php

namespace Oro\Bundle\CatalogBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Tree\Entity\Repository\NestedTreeRepository;

class CategoryRepository extends NestedTreeRepository
{
    /**
     * @var Category
     */
    private $masterCatalog;

    /**
     * @return Category
     */
    public function getMasterCatalogRoot()
    {
        if (!$this->masterCatalog) {
            $this->masterCatalog = $this->createQueryBuilder('category')
                ->addSelect('titles')
                ->leftJoin('category.titles', 'titles')
                ->andWhere('category.parentCategory IS NULL')
                ->orderBy('category.id', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult();
        }
        return $this->masterCatalog;
    }

    /**
     * @param object|null $node
     * @param bool $direct
     * @param string|null $sortByField
     * @param string $direction
     * @param bool $includeNode
     * @return QueryBuilder
     */
    public function getChildrenQueryBuilderPartial(
        $node = null,
        $direct = false,
        $sortByField = null,
        $direction = 'ASC',
        $includeNode = false
    ) {
        return $this->getChildrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode)
            ->select('partial node.{id, parentCategory, left, level, right, root}');
    }

    /**
     * @param object|null $node
     * @param bool $direct
     * @param string|null $sortByField
     * @param string $direction
     * @param bool $includeNode
     * @return Category[]
     */
    public function getChildrenWithTitles(
        $node = null,
        $direct = false,
        $sortByField = null,
        $direction = 'ASC',
        $includeNode = false
    ) {
        return $this->getChildrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode)
            ->addSelect('title, children')
            ->leftJoin('node.titles', 'title')
            ->leftJoin('node.childCategories', 'children')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Category $category
     * @return array
     */
    public function getChildrenIds(Category $category)
    {
        $result = $this->childrenQueryBuilder($category)
            ->select('node.id')
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $result);
    }

    /**
     * @param string $title
     * @return Category|null
     */
    public function findOneByDefaultTitle($title)
    {
        $qb = $this->createQueryBuilder('category');

        return $qb
            ->select('partial category.{id}')
            ->innerJoin('category.titles', 'title', Join::WITH, $qb->expr()->isNull('title.localization'))
            ->andWhere('title.string = :title')
            ->setParameter('title', $title)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Product $product
     *
     * @return Category|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByProduct(Product $product)
    {
        return $this->createQueryBuilder('category')
            ->where(':product MEMBER OF category.products')
            ->setParameter('product', $product)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $productSku
     *
     * @param bool $includeTitles
     * @return null|Category
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByProductSku($productSku, $includeTitles = false)
    {
        $qb = $this->createQueryBuilder('category');

        if ($includeTitles) {
            $qb->addSelect('title.string');
            $qb->leftJoin('category.titles', 'title', Join::WITH, $qb->expr()->isNull('title.localization'));
        }

        return $qb
            ->select('partial category.{id}')
            ->innerJoin('category.products', 'p', Join::WITH, $qb->expr()->eq('p.sku', ':sku'))
            ->setParameter('sku', $productSku)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getCategoriesProductsCountQueryBuilder($categories)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('category.id, COUNT(product.id) as products_count')
            ->from('OroProductBundle:Product', 'product')
            ->innerJoin(
                'OroCatalogBundle:Category',
                'category',
                Expr\Join::WITH,
                'product MEMBER OF category.products'
            )
            ->where($qb->expr()->in('category.id', ':categories'))
            ->setParameter('categories', $categories)
            ->groupBy('category.id');

        return $qb;
    }

    /**
     * @param Category $category
     * @return Category[]
     */
    public function getAllChildCategories(Category $category)
    {
         return $this->getChildrenQueryBuilderPartial($category)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Category $category
     * @return Category[]
     */
    public function getChildrenWithPath(Category $category)
    {
        return $this->getChildrenQueryBuilder($category)
            ->select('partial node.{id, parentCategory, materializedPath}')
            ->getQuery()
            ->getResult();
    }
}
