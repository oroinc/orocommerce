<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Contains business specific methods for retrieving product entities.
 */
class ProductRepository extends ServiceEntityRepository
{
    /**
     * @param mixed $skus
     *
     * @return QueryBuilder
     */
    public function getBySkuQueryBuilder($skus)
    {
        /** @var array $skus */
        $skus = !is_array($skus) ? [$skus] : $skus;
        foreach ($skus as $key => $sku) {
            $skus[$key] = mb_strtoupper($sku);
        }

        $queryBuilder = $this->createQueryBuilder('product');
        $queryBuilder->where($queryBuilder->expr()->in('product.skuUppercase', ':skus'))
            ->setParameter('skus', $skus);

        return $queryBuilder;
    }

    /**
     * @param string $pattern
     *
     * @return QueryBuilder
     */
    public function getAllSkuByPatternQueryBuilder($pattern)
    {
        $queryBuilder = $this->createQueryBuilder('product');
        $queryBuilder->select('product.sku')
            ->where($queryBuilder->expr()->like('product.sku', ':pattern'))
            ->setParameter('pattern', $pattern);

        return $queryBuilder;
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
                ->where($productsQueryBuilder->expr()->in('p.id', ':productIds'))
                ->setParameter('productIds', $productIds);
        }

        return $productsQueryBuilder;
    }

    public function getProductIdBySkuQueryBuilder(string $sku): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->resetDQLPart('select')
            ->select('p.id');

        return $qb->where($qb->expr()->eq('p.skuUppercase', ':product_sku'))
            ->setParameter('product_sku', mb_strtoupper($sku));
    }

    /**
     * This method is searching for products, not using any joined
     * tables for fast performance.
     *
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
            ->where(
                $productsQueryBuilder->expr()->orX(
                    $productsQueryBuilder->expr()->like('p.skuUppercase', ':search'),
                    $productsQueryBuilder->expr()->like('p.denormalizedDefaultNameUppercase', ':search')
                )
            )
            ->setParameter('search', '%' . mb_strtoupper($search) . '%')
            ->addOrderBy('p.id')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        return $productsQueryBuilder;
    }

    private function getImagesQueryBuilder(array $productIds): QueryBuilder
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('imageFile as image, IDENTITY(pi.product) as product_id')
            ->from(File::class, 'imageFile')
            ->join(
                ProductImage::class,
                'pi',
                Expr\Join::WITH,
                'imageFile.id = pi.image'
            );

        $qb->where($qb->expr()->in('pi.product', ':products'))
            ->setParameter('products', $productIds);

        return $qb->join('pi.types', 'imageTypes');
    }

    /**
     * @param array $productIds
     * @return File[]
     */
    public function getListingImagesFilesByProductIds(array $productIds)
    {
        $qb = $this->getImagesQueryBuilder($productIds);

        $productImages = $qb->andWhere($qb->expr()->eq('imageTypes.type', ':imageType'))
            ->setParameter('imageType', ProductImageType::TYPE_LISTING)
            ->getQuery()->execute();

        $images = [];
        foreach ($productImages as $productImage) {
            $images[$productImage['product_id']] = $productImage['image'];
        }

        return $images;
    }

    /**
     * @param array $productIds
     * @return File[]
     */
    public function getListingAndMainImagesFilesByProductIds(array $productIds)
    {
        $qb = $this->getImagesQueryBuilder($productIds);

        $productImages = $qb->addSelect('imageTypes.type as type')
            ->andWhere($qb->expr()->in('imageTypes.type', ':imageTypes'))
            ->setParameter('imageTypes', [ProductImageType::TYPE_LISTING, ProductImageType::TYPE_MAIN])
            ->getQuery()->execute();

        $images = [];
        foreach ($productImages as $productImage) {
            if ($productImage['type'] === ProductImageType::TYPE_LISTING) {
                $images[$productImage['product_id']][ProductImageType::TYPE_LISTING] = $productImage['image'];
            }

            if ($productImage['type'] === ProductImageType::TYPE_MAIN) {
                $images[$productImage['product_id']][ProductImageType::TYPE_MAIN] = $productImage['image'];
            }
        }

        return $images;
    }

    /**
     * @param int $productId
     *
     * @return File[]
     */
    public function getImagesFilesByProductId($productId)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('imageFile')
            ->from(File::class, 'imageFile')
            ->join(
                ProductImage::class,
                'pi',
                Expr\Join::WITH,
                'imageFile.id = pi.image'
            );

        $qb->where($qb->expr()->eq('pi.product', ':product_id'))
            ->setParameter('product_id', $productId);

        return $qb->getQuery()->execute();
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * $variantParameters = [
     *     'size' => 'm',
     *     'color' => 'red',
     *     'slim_fit' => true
     * ]
     * Value is extended field id for select field and true or false for boolean field
     * @return QueryBuilder
     */
    public function getSimpleProductsByVariantFieldsQueryBuilder(Product $configurableProduct, array $variantParameters)
    {
        $qb = $this
            ->createQueryBuilder('p')
            ->select('p')
            ->leftJoin('p.parentVariantLinks', 'l')
            ->andWhere('l.parentProduct = :parentProduct')
            ->setParameter('parentProduct', $configurableProduct);

        foreach ($variantParameters as $variantName => $variantValue) {
            QueryBuilderUtil::checkIdentifier($variantName);
            QueryBuilderUtil::checkIdentifier($variantValue);
            $qb
                ->andWhere(sprintf('p.%s = :variantValue%s', $variantName, $variantName))
                ->setParameter(sprintf('variantValue%s', $variantName), $variantValue);
        }

        return $qb;
    }

    /**
     * @param int[] $configurableProductIds
     *
     * @return QueryBuilder
     */
    public function getSimpleProductIdsByParentProductsQueryBuilder(array $configurableProductIds): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('p.id')
            ->from($this->getEntityName(), 'p')
            ->innerJoin('p.parentVariantLinks', 'l')
            ->andWhere($qb->expr()->in('l.parentProduct', ':configurableProducts'))
            ->setParameter('configurableProducts', $configurableProductIds);

        return $qb;
    }

    /**
     * @param array $criteria
     * @return Product[]
     * @throws \LogicException
     */
    public function findByCaseInsensitive(array $criteria)
    {
        $queryBuilder = $this->createQueryBuilder('product');
        $metadata = $this->getClassMetadata();

        foreach ($criteria as $fieldName => $fieldValue) {
            QueryBuilderUtil::checkIdentifier($fieldName);
            if (!is_string($fieldValue)) {
                $productFieldName = sprintf('product.%s', $fieldName);
                $queryBuilder
                    ->andWhere($queryBuilder->expr()->eq($productFieldName, ':' . $fieldName))
                    ->setParameter($fieldName, $fieldValue);

                continue;
            }

            $parameterName = $fieldName . 'Value';

            $productFieldName = $fieldName . 'Uppercase';
            if ($metadata->hasField($productFieldName)) {
                $productFieldName = sprintf('product.%s', $productFieldName);
            } else {
                $productFieldName = sprintf('UPPER(product.%s)', $fieldName);
            }

            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq($productFieldName, ':' . $parameterName))
                ->setParameter($parameterName, mb_strtoupper($fieldValue));
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int $quantity
     *
     * @return QueryBuilder
     */
    public function getFeaturedProductsQueryBuilder($quantity)
    {
        $queryBuilder = $this->createQueryBuilder('product')
            ->select('product')
            ->setMaxResults($quantity)
            ->orderBy('product.id', 'ASC');

        $this->filterByImageType($queryBuilder);

        return $queryBuilder;
    }

    public function findParentSkusByAttributeOptions(string $type, string $fieldName, array $attributeOptions): array
    {
        QueryBuilderUtil::checkField($fieldName);
        $qb = $this->createQueryBuilder('p');
        $attr = sprintf("JSON_EXTRACT(%s.serialized_data,'%s')", 'p', $fieldName);
        $result = $qb
            ->select(['parent_product.sku', $attr . ' AS id'])
            ->distinct()
            ->join('p.parentVariantLinks', 'variant_links')
            ->join('variant_links.parentProduct', 'parent_product')
            ->where('p.type = :type')
            ->andWhere($qb->expr()->in($attr, ':attributeOptions'))
            ->orderBy('parent_product.sku')
            ->setParameter('attributeOptions', $attributeOptions)
            ->setParameter('type', $type)
            ->getQuery()
            ->getArrayResult();

        $flattenedResult = [];
        foreach ($result as $item) {
            $flattenedResult[$item['id']][] = $item['sku'];
        }

        return $flattenedResult;
    }

    /**
     * Returns array of product ids that have required attribute in their attribute family
     *
     * @param array $attributesId
     * @return array
     */
    public function getProductIdsByAttributesId(array $attributesId)
    {
        $qb = $this->createQueryBuilder('p');
        $expr = $qb->expr();

        $result = $qb
            ->resetDQLPart('select')
            ->select('p.id')
            ->innerJoin('p.attributeFamily', 'f')
            ->innerJoin('f.attributeGroups', 'g')
            ->innerJoin('g.attributeRelations', 'r')
            ->where($expr->in('r.entityConfigFieldId', ':ids'))
            ->setParameter('ids', $attributesId, Connection::PARAM_INT_ARRAY)
            ->orderBy('p.id')
            ->groupBy('p.id')
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'id');
    }

    /**
     * Returns array of product ids that have required attribute families
     *
     * @param array $attributeFamilies
     * @return array
     */
    public function getProductIdsByAttributeFamilies(array $attributeFamilies)
    {
        $qb = $this->createQueryBuilder('p');

        $result = $qb
            ->resetDQLPart('select')
            ->select('p.id')
            ->where('IDENTITY(p.attributeFamily) IN (:families)')
            ->setParameter('families', $attributeFamilies)
            ->orderBy('p.id')
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'id');
    }

    public function getVariantIdsByConfigurableProductIds(array $configurableProductIds): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(ProductVariantLink::class, 'pvl')
            ->select('IDENTITY(pvl.parentProduct) as parentId', 'IDENTITY(pvl.product) as variantId')
            ->where($qb->expr()->in('pvl.parentProduct', ':parentProduct'))
            ->setParameter('parentProduct', $configurableProductIds);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param int[] $configurableProductIds
     *
     * @return array [variant id => [parent Product Id, ...], ...]
     */
    public function getVariantsMapping(array $configurableProductIds): array
    {
        $mapping = [];

        foreach ($this->getVariantIdsByConfigurableProductIds($configurableProductIds) as $mappingRow) {
            $mapping[$mappingRow['variantId']][] = $mappingRow['parentId'];
        }

        return $mapping;
    }

    /**
     * @param Product $configurableProduct
     *
     * @return Product[]|\Generator
     */
    public function getVariantsLinksProducts(Product $configurableProduct): \Generator
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(ProductVariantLink::class, 'pvl')
            ->select('pvl')
            ->where($qb->expr()->in('pvl.parentProduct', ':parentProduct'))
            ->setParameter('parentProduct', $configurableProduct);

        /** @var ProductVariantLink $link */
        foreach ($qb->getQuery()->toIterable() as $link) {
            yield $link->getProduct();
            $this->_em->detach($link);
        }
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getRequiredAttributesForSimpleProduct(Product $product)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('cp.id', 'cp.sku', 'cp.variantFields')
            ->from(Product::class, 'cp')
            ->innerJoin('cp.variantLinks', 'vl')
            ->where($qb->expr()->eq('vl.product', ':simpleProduct'))
            ->setParameter('simpleProduct', $product);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param Product $product
     * @return Product[]|array
     */
    public function getParentProductsForSimpleProduct(Product $product)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->innerJoin('p.variantLinks', 'vl')
            ->where($qb->expr()->eq('vl.product', ':simpleProduct'))
            ->setParameter('simpleProduct', $product);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Product $product
     * @return Product[]|array
     */
    public function getSimpleProductsForConfigurableProduct(Product $product)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->innerJoin('p.parentVariantLinks', 'pvl')
            ->where($qb->expr()->eq('pvl.parentProduct', ':configurableProduct'))
            ->setParameter('configurableProduct', $product);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Product[]
     */
    public function getProductKitsByRequiredProduct(Product $product): array
    {
        $qb = $this->createQueryBuilder('pk');

        return $qb
            ->innerJoin(
                'pk.kitItems',
                'pki',
                Join::WITH,
                $qb->expr()->neq('pki.optional', ':optional')
            )
            ->setParameter('optional', true, Types::BOOLEAN)
            ->innerJoin('pki.kitItemProducts', 'pip')
            ->innerJoin(
                'pip.product',
                'p',
                Join::WITH,
                $qb->expr()->eq('p.id', ':product_id')
            )
            ->setParameter('product_id', $product->getId(), Types::INTEGER)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array $ids
     * @return int[]
     */
    public function getProductKitIdsByProductIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $qb = $this->getProductKitsByProductIdsQueryBuilder($ids);

        return $qb
            ->select('pk.id')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR_COLUMN);
    }

    /**
     * @param array $ids
     * @return Product[]|array
     */
    public function getProductKitsByProductIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        return $this
            ->getProductKitsByProductIdsQueryBuilder($ids)
            ->getQuery()
            ->getResult();
    }

    public function getProductCountQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('product')
            ->select('COUNT(product.id)');
    }

    /**
     * @return array<int,ArrayCollection<ProductName>>
     */
    public function getProductNamesByProductIds(array $productIds): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('names')
            ->from(ProductName::class, 'names')
            ->where($queryBuilder->expr()->in('names.product', ':productIds'))
            ->setParameter('productIds', $productIds, Connection::PARAM_INT_ARRAY);

        $result = [];
        foreach ($queryBuilder->getQuery()->toIterable() as $item) {
            $productId = $item->getProduct()->getId();
            if (!array_key_exists($productId, $result)) {
                $result[$productId] = new ArrayCollection();
            }
            $result[$productId]->add($item);
        }

        return $result;
    }

    /**
     * @param array $ids
     * @return QueryBuilder
     */
    private function getProductKitsByProductIdsQueryBuilder(array $ids): QueryBuilder
    {
        $qb = $this->createQueryBuilder('pk');

        return $qb
            ->innerJoin('pk.kitItems', 'pki')
            ->innerJoin('pki.kitItemProducts', 'pip')
            ->innerJoin('pip.product', 'p')
            ->where($qb->expr()->eq('pk.type', ':type'))
            ->andWhere($qb->expr()->in('p.id', ':ids'))
            ->setParameter('type', Product::TYPE_KIT, Types::STRING)
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->groupBy('pk.id');
    }

    private function filterByImageType(QueryBuilder $queryBuilder)
    {
        $parentAlias = $queryBuilder->getRootAliases()[0];

        $subQuery = $this->getEntityManager()->createQueryBuilder();
        $subQuery->select('pi.id')
            ->from(ProductImage::class, 'pi')
            ->innerJoin('pi.types', 'types')
            ->where($subQuery->expr()->eq('pi.product', $parentAlias))
            ->andWhere($subQuery->expr()->eq('types.type', ':imageType'));

        $queryBuilder
            ->andWhere($queryBuilder->expr()->exists($subQuery->getDQL()))
            ->setParameter('imageType', ProductImageType::TYPE_LISTING);
    }
}
