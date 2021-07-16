<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;

/**
 * This class provides information based on relation between product collection content variants and segments.
 */
class ContentVariantSegmentProvider
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var WebCatalogUsageProviderInterface
     */
    private $webCatalogUsageProvider;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function setWebCatalogUsageProvider(WebCatalogUsageProviderInterface $webCatalogUsageProvider)
    {
        $this->webCatalogUsageProvider = $webCatalogUsageProvider;
    }

    /**
     * @return BufferedQueryResultIterator|\Iterator|Segment[]
     */
    public function getContentVariantSegments()
    {
        if (!$this->getContentVariantClass()) {
            return new \EmptyIterator();
        }

        $queryBuilder = $this->getContentVariantSegmentQueryBuilder($this->getContentVariantQueryBuilder());

        return new BufferedQueryResultIterator($queryBuilder);
    }

    /**
     * @param int $websiteId
     * @return BufferedQueryResultIterator|\Iterator|Segment[]
     */
    public function getContentVariantSegmentsByWebsiteId(int $websiteId)
    {
        if (!$this->webCatalogUsageProvider || !$this->getContentVariantClass()) {
            return new \EmptyIterator();
        }

        $webCatalogAssignments = $this->webCatalogUsageProvider->getAssignedWebCatalogs();
        if (empty($webCatalogAssignments[$websiteId])) {
            return new \EmptyIterator();
        }

        $contentVariantQueryBuilder = $this->getContentVariantQueryBuilder();
        $contentVariantQueryBuilder->innerJoin('contentVariant.node', 'node');
        $expr = $contentVariantQueryBuilder->expr();
        $contentVariantQueryBuilder->where($expr->eq('IDENTITY(node.webCatalog)', ':webCatalog'));
        $contentVariantQueryBuilder->setParameter('webCatalog', $webCatalogAssignments[$websiteId]);

        $queryBuilder = $this->getContentVariantSegmentQueryBuilder($contentVariantQueryBuilder);

        return new BufferedQueryResultIterator($queryBuilder);
    }

    private function getContentVariantSegmentQueryBuilder(QueryBuilder $contentVariantQueryBuilder): QueryBuilder
    {
        $queryBuilder = $this->getSegmentRepository()
            ->createQueryBuilder('segment')
            ->select('DISTINCT segment');

        $queryBuilder
            ->where($queryBuilder->expr()->in('segment', $contentVariantQueryBuilder->getDQL()))
            ->orderBy('segment.id');

        /** @var Query\Parameter $parameter */
        foreach ($contentVariantQueryBuilder->getParameters() as $parameter) {
            $queryBuilder->setParameter(
                $parameter->getName(),
                $parameter->getValue(),
                $parameter->typeWasSpecified() ? $parameter->getType() : null
            );
        }

        return $queryBuilder;
    }

    private function getContentVariantQueryBuilder(): QueryBuilder
    {
        $contentVariantQueryBuilder = $this->getContentVariantRepository()
            ->createQueryBuilder('contentVariant')
            ->select('IDENTITY(contentVariant.product_collection_segment)');

        $contentVariantQueryBuilder
            ->where($contentVariantQueryBuilder->expr()->isNotNull('contentVariant.product_collection_segment'));

        return $contentVariantQueryBuilder;
    }

    /**
     * @param Segment $segment
     * @return ContentNodeInterface|null
     */
    public function getContentNode(Segment $segment)
    {
        $contentVariants = iterator_to_array($this->getContentVariants($segment));
        if (empty($contentVariants)) {
            return null;
        }

        $contentVariant = reset($contentVariants);

        return $contentVariant->getNode();
    }

    /**
     * @param Segment $segment
     * @return BufferedQueryResultIterator|\Iterator|ContentVariantInterface[]
     */
    public function getContentVariants(Segment $segment)
    {
        if (!$this->getContentVariantClass()) {
            return new \EmptyIterator();
        }

        $queryBuilder = $this->getContentVariantRepository()
            ->createQueryBuilder('contentVariant');

        $queryBuilder
            ->where($queryBuilder->expr()->eq('contentVariant.product_collection_segment', ':segment'))
            ->setParameter('segment', $segment)
            ->orderBy('contentVariant.id');

        return new BufferedQueryResultIterator($queryBuilder);
    }

    /**
     * @param Segment $segment
     * @return bool
     */
    public function hasContentVariant(Segment $segment)
    {
        if (!$this->getContentVariantClass()) {
            return false;
        }

        $queryBuilder = $this->getContentVariantRepository()
            ->createQueryBuilder('contentVariant')
            ->select('1');

        $queryBuilder
            ->where($queryBuilder->expr()->eq('contentVariant.product_collection_segment', ':segment'))
            ->setParameter('segment', $segment)
            ->setMaxResults(1);

        return !empty($queryBuilder->getQuery()->getResult());
    }

    /**
     * @return EntityRepository|null
     */
    private function getSegmentRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(Segment::class);
    }

    /**
     * @return EntityRepository|null
     */
    private function getContentVariantRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass($this->getContentVariantClass());
    }

    /**
     * @return null|string
     */
    private function getContentVariantClass()
    {
        $em = $this->doctrineHelper->getEntityManager(Segment::class);
        $metadata = $em->getClassMetadata(ContentVariantInterface::class);
        if ($metadata) {
            return $metadata->getName();
        }

        return null;
    }
}
