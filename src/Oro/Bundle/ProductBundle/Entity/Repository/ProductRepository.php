<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Contains business specific methods for retrieving product entities.
 */
class ProductRepository extends EntityRepository
{
    /**
     * @param string $sku
     *
     * @return null|Product
     */
    public function findOneBySku($sku)
    {
        $queryBuilder = $this->createQueryBuilder('product');

        $queryBuilder->andWhere('product.skuUppercase = :sku')
            ->setParameter('sku', strtoupper($sku));

        return $queryBuilder->getQuery()->getOneOrNullResult();
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
                ->where($productsQueryBuilder->expr()->in('p.id', ':productIds'))
                ->setParameter('productIds', $productIds);
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
            // Convert to uppercase for insensitive search in all DB
            $upperCaseSkus = array_map("strtoupper", $productSkus);

            $productsQueryBuilder
                ->where($productsQueryBuilder->expr()->in('p.skuUppercase', ':product_skus'))
                ->setParameter('product_skus', $upperCaseSkus);
        }

        $productsData = $productsQueryBuilder
            ->orderBy($productsQueryBuilder->expr()->asc('p.id'))
            ->getQuery()
            ->getArrayResult();

        $productsSkusToIds = [];
        foreach ($productsData as $key => $productData) {
            $productsSkusToIds[$productData['sku']] = $productData['id'];
            unset($productsData[$key]);
        }

        return $productsSkusToIds;
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

    /**
     * This method is searching for products
     * through skus and localized product names.
     *
     * @param $search
     * @param $firstResult
     * @param $maxResults
     * @return QueryBuilder
     */
    public function getLocalizedSearchQueryBuilder($search, $firstResult, $maxResults)
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
     *
     * @deprecated Since 1.5 "name" is available as a column in product table
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
     *
     * @deprecated Since 1.5 "name" is available as a column in product table
     */
    public function selectNames(QueryBuilder $queryBuilder)
    {
        $queryBuilder
            ->addSelect('product_names')
            ->innerJoin(
                'product.names',
                'product_names',
                Expr\Join::WITH,
                $queryBuilder->expr()->isNull('product_names.localization')
            );

        return $this;
    }

    /**
     * @param array $skus
     * @return QueryBuilder
     */
    public function getProductWithNamesBySkuQueryBuilder(array $skus)
    {
        // Convert to uppercase for insensitive search in all DB
        $upperCaseSkus = array_map("strtoupper", $skus);

        $qb = $this->createQueryBuilder('product')
            ->select('product');
        $qb->where($qb->expr()->in('product.skuUppercase', ':product_skus'))
            ->setParameter('product_skus', $upperCaseSkus);

        return $qb;
    }

    /**
     * @param array $skus
     * @return Product[]
     */
    public function getProductWithNamesBySku(array $skus)
    {
        $qb = $this->getProductWithNamesBySkuQueryBuilder($skus);

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
            ->where($queryBuilder->expr()->in('product.skuUppercase', ':product_skus'))
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
     * @param string $sku
     * @return string|null
     */
    public function getPrimaryUnitPrecisionCode($sku)
    {
        $qb = $this->createQueryBuilder('product');

        return $qb
            ->select('IDENTITY(productPrecision.unit)')
            ->innerJoin('product.primaryUnitPrecision', 'productPrecision')
            ->where($qb->expr()->eq('product.skuUppercase', ':sku'))
            ->setParameter('sku', strtoupper($sku))
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * @param array $ids
     * @return Product[]
     */
    public function getProductsByIds(array $ids)
    {
        $queryBuilder = $this->getProductsQueryBuilder($ids)
            ->orderBy('p.id');

        return $queryBuilder->getQuery()->getResult();
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
                throw new \LogicException(sprintf('Value of %s must be string', $fieldName));
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

    /**
     * @param $type
     * @param $fieldName
     * @param $fieldValue
     * @param $isRelationField
     * @return mixed
     */
    public function findByAttributeValue($type, $fieldName, $fieldValue, $isRelationField)
    {
        QueryBuilderUtil::checkIdentifier($fieldName);
        if ($isRelationField) {
            return $this->createQueryBuilder('p')
                ->select('p')
                ->join('p.' . $fieldName, 'attr')
                ->where('attr = :valueId')
                ->setParameter('valueId', $fieldValue)
                ->andWhere('p.type = :type')
                ->setParameter('type', $type)
                ->getQuery()
                ->getResult();
        } else {
            return $this->findBy([
                'type' => $type,
                $fieldName => $fieldValue
            ]);
        }
    }

    /**
     * @param string $type
     * @param string $fieldName
     * @param mixed $fieldValue
     * @return array
     */
    public function findParentSkusByAttributeValue(string $type, string $fieldName, $fieldValue)
    {
        $result = $this->createQueryBuilder('p')
            ->select('parent_product.sku')
            ->distinct()
            ->join(QueryBuilderUtil::getField('p', $fieldName), 'attr')
            ->join('p.parentVariantLinks', 'variant_links')
            ->join('variant_links.parentProduct', 'parent_product')
            ->where('attr = :valueId')
            ->andWhere('p.type = :type')
            ->orderBy('parent_product.sku')
            ->setParameter('valueId', $fieldValue)
            ->setParameter('type', $type)
            ->getQuery()
            ->getArrayResult();

        $flattenedResult = [];

        foreach ($result as $item) {
            $flattenedResult[$item['sku']] = $item['sku'];
        }

        return $flattenedResult;
    }

    /**
     * This is analog of  self::findParentSkusByAttributeValue method but fetches results for array of options
     * to not to execute query for each option
     * Added new method for BC purposes only
     * @param string $type
     * @param string $fieldName
     * @param array $attributeOptions
     * @return array
     */
    public function findParentSkusByAttributeOptions(string $type, string $fieldName, array $attributeOptions)
    {
        $qb = $this->createQueryBuilder('p');
        $aliasedFieldName = QueryBuilderUtil::getField('p', $fieldName);

        $result = $qb
            ->select(['parent_product.sku', 'attr.id'])
            ->distinct()
            ->join($aliasedFieldName, 'attr')
            ->join('p.parentVariantLinks', 'variant_links')
            ->join('variant_links.parentProduct', 'parent_product')
            ->where($qb->expr()->in('attr', ':attributeOptions'))
            ->andWhere('p.type = :type')
            ->andWhere($qb->expr()->isNotNull($aliasedFieldName))
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
     * @param FieldConfigModel $attribute
     * @return array
     */
    public function getProductIdsByAttribute(FieldConfigModel $attribute)
    {
        return $this->getProductIdsByAttributeId($attribute->getId());
    }

    /**
     * Returns array of product ids that have required attribute in their attribute family
     *
     * @param int $attributeId
     * @return array
     */
    public function getProductIdsByAttributeId($attributeId)
    {
        $qb = $this->createQueryBuilder('p');

        $result = $qb
            ->resetDQLPart('select')
            ->select('p.id')
            ->innerJoin('p.attributeFamily', 'f')
            ->innerJoin('f.attributeGroups', 'g')
            ->innerJoin('g.attributeRelations', 'r')
            ->where('r.entityConfigFieldId = :id')
            ->setParameter('id', $attributeId)
            ->orderBy('p.id')
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

    /**
     * @param QueryBuilder $queryBuilder
     */
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
