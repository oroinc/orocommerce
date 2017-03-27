<?php

namespace Oro\Bundle\SEOBundle\Limiter;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SEOBundle\Entity\WebCatalogProductLimitation;
use Oro\Bundle\SEOBundle\Sitemap\Provider\WebCatalogScopeCriteriaProvider;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Component\Website\WebsiteInterface;

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
        $this->insertQueryExecutor->execute(
            WebCatalogProductLimitation::class,
            ['productId', 'version'],
            $this->getWebCatalogDirectProductIds($version, $website)
        );

        $webCatalogCategoriesQueryBuilder = $this->getWebCatalogCategoriesQueryBuilder($website);
        $categoriesProductIdsSQL = 'SELECT product_id, ? FROM oro_category_to_product WHERE category_id IN ('
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
            'product_id, version',
            $categoriesProductIdsSQL
        );

        $this->nativeQueryExecutorHelper
            ->getManager(WebCatalogProductLimitation::class)
            ->getConnection()
            ->executeUpdate($sql, $params, $types);
    }

    /**
     * Erase `WebCatalogProductLimitation` table
     * Truncate `WebCatalogProductLimitation` required to avoid accumulation autoincremented `id`  in the table.
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

        if (empty($em->getRepository(WebCatalogProductLimitation::class)->findAll())) {
            $connection = $em->getConnection();
            $query = $connection->getDatabasePlatform()
                ->getTruncateTableSQL($em->getClassMetadata(WebCatalogProductLimitation::class)->getTableName());

            $connection->executeUpdate($query);
        }
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

        $qb->select('IDENTITY(productContentVariant.product_page_product), '. (int)$version)
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
            ->setParameter('productPageType', 'product_page')
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
            ->setParameter('categoryPageType', 'category_page')
            ->setParameter(
                'webCatalog',
                $this->webCatalogProvider->getWebCatalog($website)
            );

        $this->getScopeCriteria($website)->applyWhereWithPriority($qb, 'categoryScopes');

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
}
