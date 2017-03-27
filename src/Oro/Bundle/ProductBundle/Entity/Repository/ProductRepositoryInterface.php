<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository;

use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Entity\Product;

interface ProductRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @param string $sku
     *
     * @return null|Product
     */
    public function findOneBySku($sku);

    /**
     * @param string $pattern
     *
     * @return string[]
     */
    public function findAllSkuByPattern($pattern);

    /**
     * @param array $productIds
     *
     * @return QueryBuilder
     */
    public function getProductsQueryBuilder(array $productIds = []);

    /**
     * @param array $productSkus
     *
     * @return array Ids
     */
    public function getProductsIdsBySku(array $productSkus = []);

    /**
     * @param string $search
     * @param int    $firstResult
     * @param int    $maxResults
     *
     * @return QueryBuilder
     */
    public function getSearchQueryBuilder($search, $firstResult, $maxResults);

    /**
     * @return QueryBuilder
     */
    public function getProductWithNamesQueryBuilder();

    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return $this
     */
    public function selectNames(QueryBuilder $queryBuilder);

    /**
     * @param array $skus
     *
     * @return QueryBuilder
     */
    public function getProductWithNamesBySkuQueryBuilder(array $skus);

    /**
     * @param array $skus
     *
     * @return Product[]
     */
    public function getProductWithNamesBySku(array $skus);

    /**
     * @param array $skus
     *
     * @return QueryBuilder
     */
    public function getFilterSkuQueryBuilder(array $skus);

    /**
     * @param array $skus
     *
     * @return QueryBuilder
     */
    public function getFilterProductWithNamesQueryBuilder(array $skus);

    /**
     * @param array $productIds
     *
     * @return File[]
     */
    public function getListingImagesFilesByProductIds(array $productIds);

    /**
     * @param string $sku
     *
     * @return string|null
     */
    public function getPrimaryUnitPrecisionCode($sku);

    /**
     * @param array $ids
     *
     * @return Product[]
     */
    public function getProductsByIds(array $ids);

    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return $this
     */
    public function selectImages(QueryBuilder $queryBuilder);

    /**
     * @param Product $configurableProduct
     * @param array   $variantParameters
     *     $variantParameters = [
     *     'size' => 'm',
     *     'color' => 'red',
     *     'slim_fit' => true
     *     ]
     *     Value is extended field id for select field and true or false for boolean field
     *
     * @return QueryBuilder
     */
    public function getSimpleProductsByVariantFieldsQueryBuilder(
        Product $configurableProduct,
        array $variantParameters
    );

    /**
     * @param array $criteria
     *
     * @return Product[]
     * @throws \LogicException
     */
    public function findByCaseInsensitive(array $criteria);

    /**
     * @param int $quantity
     *
     * @return QueryBuilder
     */
    public function getFeaturedProductsQueryBuilder($quantity);
}
