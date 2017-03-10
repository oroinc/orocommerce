<?php

namespace Oro\Bundle\SEOBundle\Limiter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SEOBundle\Entity\WebCatalogProductLimitation;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Website\WebsiteInterface;

class WebCatalogProductLimiter
{
    const BATCH_SIZE = 10000;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     * @param ScopeManager $scopeManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        ScopeManager $scopeManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param int $version
     * @param WebsiteInterface $website
     */
    public function prepareLimitation($version, WebsiteInterface $website = null)
    {
        $em = $this->doctrineHelper->getEntityManager(WebCatalogProductLimitation::class);

        $this->fillProductIds($em, $this->getWebCatalogDirectProductIds($website), $version);
        $this->fillProductIds($em, $this->getWebCatalogCategoriesProductIds($website), $version);
    }

    /**
     * @param EntityManager $em
     * @param array $productIds
     * @param int $version
     */
    private function fillProductIds(EntityManager $em, array $productIds, $version)
    {
        $inserted = 0;
        foreach ($productIds as $productId) {
            $productLimitation = new WebCatalogProductLimitation();
            $productLimitation->setProductId($productId);
            $productLimitation->setVersion($version);

            $em->persist($productLimitation);

            $inserted++;
            if (($inserted % self::BATCH_SIZE) === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();
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
     * @param WebsiteInterface $website
     * @return array
     */
    private function getWebCatalogDirectProductIds(WebsiteInterface $website = null)
    {
        /** @var EntityManager $em */
        $em = $this->doctrineHelper->getEntityManager(Product::class);
        $qb = $em->createQueryBuilder();

        $qb->select('IDENTITY(productContentVariant.product_page_product)')
            ->from(ContentVariant::class, 'productContentVariant')
            ->innerJoin(
                ContentNode::class,
                'productContentNode',
                Join::WITH,
                'productContentVariant.node = productContentNode'
            )
            ->innerJoin(
                WebCatalog::class,
                'productWebCatalog',
                Join::WITH,
                'productContentNode.webCatalog = productWebCatalog'
            )
            ->leftJoin('productContentVariant.scopes', 'productScopes')
            ->where($qb->expr()->eq('productContentVariant.type', ':productPageType'))
            ->andWhere($qb->expr()->eq('productWebCatalog', ':productWebCatalogId'))
            ->setParameter('productPageType', 'product_page')
            ->setParameter(
                'productWebCatalogId',
                $this->configManager->get('oro_web_catalog.web_catalog', false, false, $website)
            );

        $this->getScopeCriteria($website)->applyWhereWithPriority($qb, 'productScopes');

        return array_map('current', $qb->getQuery()->getScalarResult());
    }

    /**
     * @param WebsiteInterface $website
     * @return array
     */
    private function getWebCatalogCategoriesProductIds(WebsiteInterface $website = null)
    {
        $result = [];
        $webCatalogCategories = $this->getWebCatalogCategories($website);
        if ($webCatalogCategories) {
            $categoryIds = implode(', ', $webCatalogCategories);
            $sql = "SELECT product_id FROM oro_category_to_product WHERE category_id IN ($categoryIds)";

            /** @var EntityManager $em */
            $em = $this->doctrineHelper->getEntityManager(Product::class);
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();

            $result = array_map('current', $stmt->fetchAll());
        }

        return $result;
    }

    /**
     * @param WebsiteInterface $website
     * @return array
     */
    private function getWebCatalogCategories(WebsiteInterface $website = null)
    {
        $categoryIds = [];
        $directCategories = $this->getWebCatalogDirectCategories($website);
        foreach ($directCategories as $category) {
            $categoryIds[] = $category->getId();
            $categoryIds = array_merge($categoryIds, $this->getCategorySubCategories($category));
        }

        return array_unique($categoryIds);
    }

    /**
     * @param WebsiteInterface $website
     * @return array
     */
    private function getWebCatalogDirectCategories(WebsiteInterface $website = null)
    {
        /** @var EntityManager $em */
        $em = $this->doctrineHelper->getEntityManager(Category::class);
        $qb = $em->createQueryBuilder();

        $qb->select('IDENTITY(categoryContentVariant.category_page_category) as categoryId')
            ->from(ContentVariant::class, 'categoryContentVariant')
            ->innerJoin(
                ContentNode::class,
                'categoryContentNode',
                Join::WITH,
                'categoryContentVariant.node = categoryContentNode'
            )
            ->innerJoin(
                WebCatalog::class,
                'categoryWebCatalog',
                Join::WITH,
                'categoryContentNode.webCatalog = categoryWebCatalog'
            )
            ->leftJoin('categoryContentVariant.scopes', 'categoryScopes')
            ->where($qb->expr()->eq('categoryContentVariant.type', ':categoryPageType'))
            ->andWhere($qb->expr()->eq('categoryWebCatalog', ':categoryWebCatalogId'))
            ->setParameter('categoryPageType', 'category_page')
            ->setParameter(
                'categoryWebCatalogId',
                $this->configManager->get('oro_web_catalog.web_catalog', false, false, $website)
            );

        $this->getScopeCriteria($website)->applyWhereWithPriority($qb, 'categoryScopes');

        return array_map(function ($item) use ($em) {
            return $em->getReference(Category::class, $item['categoryId']);
        }, $qb->getQuery()->getScalarResult());
    }

    /**
     * @param $category
     * @return array
     */
    private function getCategorySubCategories($category)
    {
        return $this->doctrineHelper->getEntityManager(Category::class)->getRepository(Category::class)
            ->getChildrenIds($category);
    }

    /**
     * @param WebsiteInterface $website
     * @return ScopeCriteria
     */
    private function getScopeCriteria(WebsiteInterface $website = null)
    {
        $webCatalogId = $this->configManager->get('oro_web_catalog.web_catalog', false, false, $website);
        $anonymousGroupId = $this->configManager->get('oro_customer.anonymous_customer_group', false, false, $website);

        $webCatalog = null;
        if ($webCatalogId) {
            $webCatalog = $this->doctrineHelper
                ->getEntityManager(WebCatalog::class)
                ->getReference(WebCatalog::class, $webCatalogId);
        }

        $anonymousGroup = null;
        if ($anonymousGroupId) {
            $anonymousGroup = $this->doctrineHelper
                ->getEntityManager(WebCatalog::class)
                ->getReference(CustomerGroup::class, $anonymousGroupId);
        }

        return $this->scopeManager->getCriteria(
            'web_content',
            [
                'website' => $website,
                'webCatalog' => $webCatalog,
                'customerGroup' => $anonymousGroup
            ]
        );
    }
}
