<?php

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductCollectionBySegmentTopic as Topic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Factory for creating MQ messages with segment and website ids, and getting data from them.
 */
class SegmentMessageFactory
{
    private ManagerRegistry $registry;
    private ?SegmentRepository $segmentRepository = null;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function createMessage(
        int $jobId,
        array $websiteIds,
        Segment $segment = null,
        string $definition = null,
        bool $isFull = true,
        array $additionalProducts = []
    ): array {
        return [
            Topic::OPTION_NAME_JOB_ID => $jobId,
            Topic::OPTION_NAME_ID => $segment?->getId(),
            Topic::OPTION_NAME_WEBSITE_IDS => $websiteIds,
            Topic::OPTION_NAME_DEFINITION => $definition,
            Topic::OPTION_NAME_IS_FULL => $isFull,
            Topic::OPTION_NAME_ADDITIONAL_PRODUCTS => $additionalProducts,
        ];
    }

    public function createMessageFromJobIdAndPartialMessage(
        int $jobId,
        array $partialMessageData
    ): array {
        return \array_merge(
            [
                Topic::OPTION_NAME_JOB_ID => $jobId
            ],
            $partialMessageData
        );
    }

    /**
     * Allows creating of intermediate message data to have possibility collect data about unique messages
     * till root job with child jobs for this type of messages will be created
     */
    public function getPartialMessageData(
        array $websiteIds,
        Segment $segment = null,
        string $definition = null,
        bool $isFull = true,
        array $additionalProducts = []
    ): array {
        return [
            Topic::OPTION_NAME_ID => $segment?->getId(),
            Topic::OPTION_NAME_WEBSITE_IDS => $websiteIds,
            Topic::OPTION_NAME_DEFINITION => $definition,
            Topic::OPTION_NAME_IS_FULL => $isFull,
            Topic::OPTION_NAME_ADDITIONAL_PRODUCTS => $additionalProducts,
        ];
    }

    public function getJobIdFromMessage(array $data): int
    {
        return $data[Topic::OPTION_NAME_JOB_ID];
    }

    /**
     * @param array $data
     * @return Segment
     * @throws InvalidArgumentException
     */
    public function getSegmentFromMessage(array $data): Segment
    {
        if (!empty($data[Topic::OPTION_NAME_ID])) {
            $segment = $this->getSegmentRepository()->find($data[Topic::OPTION_NAME_ID]);
            if (!$segment) {
                throw new InvalidArgumentException(
                    sprintf(
                        'No segment exists with id "%d"',
                        $data[Topic::OPTION_NAME_ID]
                    )
                );
            }

            return $segment;
        }

        $segment = new Segment();
        $segment->setEntity(Product::class);
        $segment->setDefinition($data[Topic::OPTION_NAME_DEFINITION]);

        return $segment;
    }

    /**
     * @param array $data
     * @return array
     */
    public function getWebsiteIdsFromMessage(array $data): array
    {
        return $data[Topic::OPTION_NAME_WEBSITE_IDS];
    }

    /**
     * @param array $data
     * @return bool
     */
    public function getIsFull(array $data): bool
    {
        return $data[Topic::OPTION_NAME_IS_FULL];
    }

    /**
     * @param array $data
     * @return array
     */
    public function getAdditionalProductsFromMessage(array $data): array
    {
        return $data[Topic::OPTION_NAME_ADDITIONAL_PRODUCTS] ?? [];
    }

    /**
     * @return SegmentRepository
     */
    private function getSegmentRepository(): SegmentRepository
    {
        if (null === $this->segmentRepository) {
            $this->segmentRepository = $this->registry->getRepository(Segment::class);
        }

        return $this->segmentRepository;
    }
}
