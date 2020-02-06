<?php

namespace Oro\Bundle\SEOBundle\Limiter;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SEOBundle\Entity\WebCatalogProductLimitation;
use Oro\Bundle\SEOBundle\Sitemap\Provider\WebCatalogScopeCriteriaProvider;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Component\Website\WebsiteInterface;

/**
 * Apply product limitation based on products engaged into web catalog
 */
class WebCatalogProductLimiter
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var WebCatalogProvider
     */
    protected $webCatalogProvider;

    /**
     * @var WebCatalogScopeCriteriaProvider
     */
    protected $scopeCriteriaProvider;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertQueryExecutor;

    /**
     * @var NativeQueryExecutorHelper
     */
    protected $nativeQueryExecutorHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param WebCatalogProvider $webCatalogProvider
     * @param WebCatalogScopeCriteriaProvider $scopeCriteriaProvider
     * @param InsertFromSelectQueryExecutor $insertQueryExecutor
     * @param NativeQueryExecutorHelper $nativeQueryExecutorHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        WebCatalogProvider $webCatalogProvider,
        WebCatalogScopeCriteriaProvider $scopeCriteriaProvider,
        InsertFromSelectQueryExecutor $insertQueryExecutor,
        NativeQueryExecutorHelper $nativeQueryExecutorHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->scopeCriteriaProvider = $scopeCriteriaProvider;
        $this->insertQueryExecutor = $insertQueryExecutor;
        $this->nativeQueryExecutorHelper = $nativeQueryExecutorHelper;
        $this->webCatalogProvider = $webCatalogProvider;
    }

    /**
     * @param int $version
     * @param WebsiteInterface $website
     */
    public function prepareLimitation($version, WebsiteInterface $website = null)
    {
        $this->addWebCatalogDirectProducts($version, $website);
        $this->addWebCatalogCollectionRelatedProducts($version, $website);
        $this->addWebCatalogCateogoryRelatedProducts($version, $website);
    }

    /**
     * Erase `WebCatalogProductLimitation` table
     *
     * @param int $version
     */
    public function erase($version)
    {
        $em = $this->doctrineHelper->getEntityManager(WebCatalogProductLimitation::class);
        $qb = $em->createQueryBuilder();
        $qb->delete(WebCatalogProductLimitation::class, 'limitation')
            ->where($qb->expr()->eq('limitation.version', ':version'))
            ->setParameter('version', $version)
            ->getQuery()
            ->execute();
    }

    /**
     * @param int $version
     * @param WebsiteInterface $website
     * @return QueryBuilder
     */
    private function getWebCatalogDirectProductIds($version, WebsiteInterface $website = null)
    {
        /** @var EntityManager $em */
        $em = $this->doctrineHelper->getEntityManager(Product::class);
        $qb = $em->createQueryBuilder();

        $qb->select('UUID(), IDENTITY(productContentVariant.product_page_product), '. (int)$version)
            ->from(ContentVariant::class, 'productContentVariant')
            ->innerJoin(
                ContentNode::class,
                'productContentNode',
                Join::WITH,
                'productContentVariant.node = productContentNode'
            )
            ->leftJoin('productContentVariant.scopes', 'productScopes')
            ->where($qb->expr()->eq('productContentVariant.type', ':productPageType'))
            ->andWhere($qb->expr()->eq('productContentNode.webCatalog', ':webCatalog'))
            ->setParameter('productPageType', ProductPageContentVariantType::TYPE)
            ->setParameter('webCatalog', $this->webCatalogProvider->getWebCatalog($website));

        $this->getScopeCriteria($website)->applyWhereWithPriority($qb, 'productScopes');

        return $qb;
    }

    /**
     * @param WebsiteInterface $website
     * @return QueryBuilder
     */
    private function getWebCatalogCategoriesQueryBuilder(WebsiteInterface $website = null)
    {
        /** @var EntityManager $em */
        $em = $this->doctrineHelper->getEntityManager(Category::class);
        $qb = $em->createQueryBuilder();

        $qb->select('category.id as categoryId')
            ->from(ContentVariant::class, 'categoryContentVariant')
            ->innerJoin(
                Category::class,
                'parentCategory',
                Join::WITH,
                $qb->expr()->eq('categoryContentVariant.category_page_category', 'parentCategory.id')
            )
            ->innerJoin(
                Category::class,
                'category',
                Join::WITH,
                $qb->expr()->orX(
                    $qb->expr()->eq('category.id', 'parentCategory.id'),
                    $qb->expr()->andX(
                        $qb->expr()->lt('category.right', 'parentCategory.right'),
                        $qb->expr()->gt('category.left', 'parentCategory.left')
                    )
                )
            )
            ->leftJoin(
                ContentNode::class,
                'categoryContentNode',
                Join::WITH,
                $qb->expr()->eq('categoryContentVariant.node', 'categoryContentNode')
            )
            ->leftJoin('categoryContentVariant.scopes', 'categoryScopes')
            ->where($qb->expr()->eq('categoryContentVariant.type', ':categoryPageType'))
            ->andWhere($qb->expr()->eq('categoryContentNode.webCatalog', ':webCatalog'))
            ->setParameter('categoryPageType', CategoryPageContentVariantType::TYPE)
            ->setParameter(
                'webCatalog',
                $this->webCatalogProvider->getWebCatalog($website)
            );

        $this->getScopeCriteria($website)->applyWhereWithPriority($qb, 'categoryScopes');

        return $qb;
    }

    /**
     * @param int $version
     * @param WebsiteInterface|null $website
     * @return QueryBuilder
     */
    private function getWebCatalogProductCollectionProducts($version, WebsiteInterface $website = null)
    {
        /** @var EntityManager $em */
        $em = $this->doctrineHelper->getEntityManager(Product::class);
        $qb = $em->createQueryBuilder();

        $qb->select('UUID(), segmentSnapshot.integerEntityId, '. (int)$version)
            ->from(ContentVariant::class, 'productCollectionContentVariant')
            ->innerJoin(
                SegmentSnapshot::class,
                'segmentSnapshot',
                Join::WITH,
                $qb->expr()->eq('productCollectionContentVariant.product_collection_segment', 'segmentSnapshot.segment')
            )
            ->innerJoin(
                ContentNode::class,
                'productCollectionContentNode',
                Join::WITH,
                'productCollectionContentVariant.node = productCollectionContentNode'
            )
            ->leftJoin('productCollectionContentVariant.scopes', 'productCollectionScopes')
            ->where($qb->expr()->eq('productCollectionContentVariant.type', ':productCollectionPageType'))
            ->andWhere($qb->expr()->eq('productCollectionContentNode.webCatalog', ':webCatalog'))
            ->setParameter('productCollectionPageType', ProductCollectionContentVariantType::TYPE)
            ->setParameter('webCatalog', $this->webCatalogProvider->getWebCatalog($website));

        $this->getScopeCriteria($website)->applyWhereWithPriority($qb, 'productCollectionScopes');

        return $qb;
    }

    /**
     * @param WebsiteInterface $website
     * @return ScopeCriteria
     */
    private function getScopeCriteria(WebsiteInterface $website = null)
    {
        return $this->scopeCriteriaProvider->getWebCatalogScopeForAnonymousCustomerGroup($website);
    }

    /**
     * @param $version
     * @param WebsiteInterface|null $website
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function addWebCatalogDirectProducts($version, WebsiteInterface $website = null): void
    {
        $this->insertQueryExecutor->execute(
            WebCatalogProductLimitation::class,
            ['id', 'productId', 'version'],
            $this->getWebCatalogDirectProductIds($version, $website)
        );
    }

    /**
     * @param $version
     * @param WebsiteInterface|null $website
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function addWebCatalogCollectionRelatedProducts($version, WebsiteInterface $website = null): void
    {
        $this->insertQueryExecutor->execute(
            WebCatalogProductLimitation::class,
            ['id', 'productId', 'version'],
            $this->getWebCatalogProductCollectionProducts($version, $website)
        );
    }

    /**
     * @param $version
     * @param WebsiteInterface|null $website
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function addWebCatalogCateogoryRelatedProducts($version, WebsiteInterface $website = null)
    {
        $webCatalogCategoriesQueryBuilder = $this->getWebCatalogCategoriesQueryBuilder($website);
        $platform = $this->doctrineHelper->getEntityManagerForClass(WebCatalogProductLimitation::class)
            ->getConnection()
            ->getDatabasePlatform();
        $nativeUUID = $platform instanceof PostgreSqlPlatform ? 'uuid_generate_v4()' : 'uuid()';
        $categoriesProductIdsSQL = 'SELECT ' . $nativeUUID . ', id, ? FROM oro_product WHERE category_id IN ('
            . $webCatalogCategoriesQueryBuilder->getQuery()->getSQL()
            . ');';

        // Get all subquery position parameters
        list($params, $types) = $this->nativeQueryExecutorHelper->processParameterMappings(
            $webCatalogCategoriesQueryBuilder->getQuery()
        );

        // Set version value into first position parameter
        array_unshift($params, $version);
        array_unshift($types, Type::INTEGER);

        $sql = sprintf(
            'insert into %s (%s) %s',
            $this->nativeQueryExecutorHelper->getTableName(WebCatalogProductLimitation::class),
            'id, product_id, version',
            $categoriesProductIdsSQL
        );

        $this->nativeQueryExecutorHelper
            ->getManager(WebCatalogProductLimitation::class)
            ->getConnection()
            ->executeUpdate($sql, $params, $types);
    }
}
