<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;

class ProductRepository extends EntityRepository
{
    /**
     * @param string $sku
     *
     * @return null|Product
     */
    public function findOneBySku($sku)
    {
        return $this->findOneBy(['sku' => $sku]);
    }

    /**
     * @param string $pattern
     *
     * @return string[]
     */
    public function findAllSkuByPattern($pattern)
    {
        $matchedSku = [];

        $results = $this
            ->createQueryBuilder('product')
            ->select('product.sku')
            ->where('product.sku LIKE :pattern')
            ->setParameter('pattern', $pattern)
            ->getQuery()
            ->getResult();

        foreach ($results as $result) {
            $matchedSku[] = $result['sku'];
        }

        return $matchedSku;
    }

    /**
     * @param array $productIds
     *
     * @return QueryBuilder
     */
    public function getProductsQueryBuilder(array $productIds = [])
    {
        $productsQueryBuilder = $this
            ->createQueryBuilder('p')
            ->select('p');

        if (count($productIds) > 0) {
            $productsQueryBuilder
                ->where($productsQueryBuilder->expr()->in('p', ':product_ids'))
                ->setParameter('product_ids', $productIds);
        }

        return $productsQueryBuilder;
    }

    /**
     * @param array $productSkus
     *
     * @return array Ids
     */
    public function getProductsIdsBySku(array $productSkus = [])
    {
        $productsQueryBuilder = $this
            ->createQueryBuilder('p')
            ->select('p.id, p.sku');

        if ($productSkus) {
            $productsQueryBuilder
                ->where($productsQueryBuilder->expr()->in('p.sku', ':product_skus'))
                ->setParameter('product_skus', $productSkus);
        }

        $productsData = $productsQueryBuilder
            ->orderBy($productsQueryBuilder->expr()->asc('p.id'))
            ->getQuery()
            ->getArrayResult();

        $productsIdsToSku = [];
        foreach ($productsData as $key => $productData) {
            $productsIdsToSku[$productData['sku']] = $productData['id'];
            unset($productsData[$key]);
        }

        return $productsIdsToSku;
    }

    /**
     * @param string $search
     * @param int $firstResult
     * @param int $maxResults
     * @return QueryBuilder
     */
    public function getSearchQueryBuilder($search, $firstResult, $maxResults)
    {
        $productsQueryBuilder = $this
            ->createQueryBuilder('p');

        $productsQueryBuilder
            ->innerJoin('p.names', 'pn', Expr\Join::WITH, $productsQueryBuilder->expr()->isNull('pn.localization'))
            ->where(
                $productsQueryBuilder->expr()->orX(
                    $productsQueryBuilder->expr()->like('LOWER(p.sku)', ':search'),
                    $productsQueryBuilder->expr()->like('LOWER(pn.string)', ':search')
                )
            )
            ->setParameter('search', '%' . strtolower($search) . '%')
            ->addOrderBy('p.id')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        return $productsQueryBuilder;
    }

    /**
     * @return QueryBuilder
     */
    public function getProductWithNamesQueryBuilder()
    {
        $queryBuilder = $this->createQueryBuilder('product')
            ->select('product');
        $this->selectNames($queryBuilder);
        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return $this
     */
    public function selectNames(QueryBuilder $queryBuilder)
    {
        $queryBuilder->addSelect('product_names')->innerJoin('product.names', 'product_names');

        return $this;
    }

    /**
     * @param array $skus
     * @return Product[]
     */
    public function getProductWithNamesBySku(array $skus)
    {
        $qb = $this->getProductWithNamesQueryBuilder();
        $qb->where($qb->expr()->in('product.sku', ':product_skus'))
            ->setParameter('product_skus', $skus);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $skus
     * @return QueryBuilder
     */
    public function getFilterSkuQueryBuilder(array $skus)
    {
        // Convert to uppercase for insensitive search in all DB
        $upperCaseSkus = array_map("strtoupper", $skus);

        $queryBuilder = $this->createQueryBuilder('product');
        $queryBuilder
            ->select('product.sku')
            ->where($queryBuilder->expr()->in('UPPER(product.sku)', ':product_skus'))
            ->setParameter('product_skus', $upperCaseSkus);
        return $queryBuilder;
    }

    /**
     * @param array $skus
     * @return QueryBuilder
     */
    public function getFilterProductWithNamesQueryBuilder(array $skus)
    {
        return $this->getFilterSkuQueryBuilder($skus)->select('product, product_names')
            ->innerJoin('product.names', 'product_names');
    }

    /**
     * @param array $productIds
     * @return File[]
     */
    public function getListingImagesFilesByProductIds(array $productIds)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('imageFile as image, IDENTITY(pi.product) as product_id')
            ->from('OroAttachmentBundle:File', 'imageFile')
            ->join(
                'OroProductBundle:ProductImage',
                'pi',
                Expr\Join::WITH,
                'imageFile.id = pi.image'
            );

        $qb->where($qb->expr()->in('pi.product', ':products'))
            ->setParameter('products', $productIds);

        $qb->join('pi.types', 'imageTypes')
            ->andWhere($qb->expr()->eq('imageTypes.type', ':imageType'))
            ->setParameter('imageType', ProductImageType::TYPE_LISTING);

        $productImages = $qb->getQuery()->execute();
        $images = [];

        foreach ($productImages as $productImage) {
            $images[$productImage['product_id']] = $productImage['image'];
        }

        return $images;
    }

    /**
     * @param string $sku
     * @return string
     */
    public function getPrimaryUnitPrecisionCode($sku)
    {
        $qb = $this->createQueryBuilder('product');

        return $qb
            ->select('IDENTITY(productPrecision.unit)')
            ->innerJoin('product.primaryUnitPrecision', 'productPrecision')
            ->where($qb->expr()->eq('product.sku', ':sku'))
            ->setParameter('sku', $sku)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array $ids
     * @return Product[]
     */
    public function getProductsByIds(array $ids)
    {
        return $this->getProductsQueryBuilder($ids)->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return $this
     */
    public function selectImages(QueryBuilder $queryBuilder)
    {
        $queryBuilder->addSelect('product_images,product_images_types,product_images_file')
            ->join('product.images', 'product_images')
            ->join('product_images.types', 'product_images_types')
            ->join('product_images.image', 'product_images_file')
            ->andWhere($queryBuilder->expr()->eq('product_images_types.type', ':imageType'))
            ->setParameter('imageType', ProductImageType::TYPE_MAIN);

        return $this;
    }
}
