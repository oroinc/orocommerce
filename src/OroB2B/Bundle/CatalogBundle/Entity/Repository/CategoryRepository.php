<?php

namespace OroB2B\Bundle\CatalogBundle\Entity\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @method CategoryRepository persistAsFirstChildOf() persistAsFirstChildOf(Category $node, Category $parent)
 * @method CategoryRepository persistAsNextSiblingOf() persistAsNextSiblingOf(Category $node, Category $sibling)
 */
class CategoryRepository extends NestedTreeRepository
{
    /**
     * @return Category
     */
    public function getMasterCatalogRoot()
    {
        return $this->createQueryBuilder('category')
            ->andWhere('category.parentCategory IS NULL')
            ->orderBy('category.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
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
            ->addSelect('title')
            ->leftJoin('node.titles', 'title')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $title
     * @return Category|null
     */
    public function findOneByDefaultTitle($title)
    {
        return $this->createQueryBuilder('category')
            ->innerJoin('category.titles', 'title')
            ->andWhere('title.string = :title')->setParameter('title', $title)
            ->andWhere('title.locale IS NULL')
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
            ->join('category.product', 'categoryProduct')
            ->where('categoryProduct = :product')
            ->setParameter('product', $product)
            ->getQuery()->getOneOrNullResult();
    }
}
