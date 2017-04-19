<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

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
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return BufferedQueryResultIterator|\Iterator|Segment[]
     */
    public function getContentVariantSegments()
    {
        if (!$this->getContentVariantClass()) {
            return new \EmptyIterator();
        }

        $contentVariantQueryBuilder = $this->getContentVariantRepository()
            ->createQueryBuilder('contentVariant')
            ->select('IDENTITY(contentVariant.product_collection_segment)');

        $contentVariantQueryBuilder
            ->where($contentVariantQueryBuilder->expr()->isNotNull('contentVariant.product_collection_segment'));

        $queryBuilder = $this->getSegmentRepository()
            ->createQueryBuilder('segment')
            ->select('DISTINCT segment');

        $queryBuilder
            ->where($queryBuilder->expr()->in('segment', $contentVariantQueryBuilder->getDQL()))
            ->orderBy('segment.id');

        return new BufferedQueryResultIterator($queryBuilder);
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
