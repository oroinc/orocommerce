<?php

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Factory for creating MQ messages with segment and website ids, and getting data from them.
 *
 * @deprecated Will be removed in v5.1
 */
class AccumulateSegmentMessageFactory
{
    public const ID = 'id';
    public const JOB_ID = 'job_id';
    public const WEBSITE_IDS = 'website_ids';
    public const DEFINITION = 'definition';
    public const IS_FULL = 'is_full';
    public const ADDITIONAL_PRODUCTS = 'additional_products';

    private ManagerRegistry $registry;
    private ?OptionsResolver $resolver = null;
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
        return $this->getResolvedData([
            self::JOB_ID => $jobId,
            self::ID => $segment ? $segment->getId() : null,
            self::WEBSITE_IDS => $websiteIds,
            self::DEFINITION => $definition,
            self::IS_FULL => $isFull,
            self::ADDITIONAL_PRODUCTS => $additionalProducts,
        ]);
    }

    public function createMessageFromJobIdAndPartialMessage(
        int $jobId,
        array $partialMessageData
    ): array {
        return $this->getResolvedData(\array_merge(
            [
                self::JOB_ID => $jobId
            ],
            $partialMessageData
        ));
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
            self::ID => $segment ? $segment->getId() : null,
            self::WEBSITE_IDS => $websiteIds,
            self::DEFINITION => $definition,
            self::IS_FULL => $isFull,
            self::ADDITIONAL_PRODUCTS => $additionalProducts,
        ];
    }

    public function getJobIdFromMessage(array $data): int
    {
        $data = $this->getResolvedData($data);

        return $data[self::JOB_ID];
    }

    /**
     * @param array $data
     * @return Segment
     * @throws InvalidArgumentException
     */
    public function getSegmentFromMessage(array $data): Segment
    {
        $data = $this->getResolvedData($data);

        if (!empty($data[self::ID])) {
            $segment = $this->getSegmentRepository()->find($data[self::ID]);
            if (!$segment) {
                throw new InvalidArgumentException(sprintf('No segment exists with id "%d"', $data[self::ID]));
            }
        } elseif (array_key_exists(self::DEFINITION, $data) && $data[self::DEFINITION]) {
            $segment = new Segment();
            $segment->setEntity(Product::class);
            $segment->setDefinition($data[self::DEFINITION]);
        } else {
            throw new InvalidArgumentException('Segment Id or Segment Definition should be present in message.');
        }

        return $segment;
    }

    /**
     * @param array $data
     * @return array
     */
    public function getWebsiteIdsFromMessage(array $data): array
    {
        $data = $this->getResolvedData($data);

        return $data[self::WEBSITE_IDS];
    }

    /**
     * @param array $data
     * @return bool
     */
    public function getIsFull(array $data): bool
    {
        $data = $this->getResolvedData($data);

        return $data[self::IS_FULL];
    }

    /**
     * @param array $data
     * @return array
     */
    public function getAdditionalProductsFromMessage(array $data): array
    {
        $data = $this->getResolvedData($data);

        return $data[self::ADDITIONAL_PRODUCTS];
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionsResolver(): OptionsResolver
    {
        if (null === $this->resolver) {
            $resolver = new OptionsResolver();

            $resolver->setRequired([
                self::JOB_ID,
                self::WEBSITE_IDS,
                self::IS_FULL,
            ]);

            $resolver->setDefined([
                self::ID,
                self::DEFINITION,
                self::ADDITIONAL_PRODUCTS,
            ]);

            $resolver->setAllowedTypes(self::JOB_ID, 'int');
            $resolver->setAllowedTypes(self::WEBSITE_IDS, 'array');
            $resolver->setAllowedTypes(self::ID, ['null','int']);
            $resolver->setAllowedTypes(self::DEFINITION, ['null', 'string']);
            $resolver->setAllowedTypes(self::IS_FULL, ['boolean']);
            $resolver->setAllowedTypes(self::ADDITIONAL_PRODUCTS, ['array']);

            $this->resolver = $resolver;
        }

        return $this->resolver;
    }

    /**
     * @param array $data
     * @return array
     * @throws InvalidArgumentException
     */
    private function getResolvedData(array $data): array
    {
        try {
            return $this->getOptionsResolver()->resolve($data);
        } catch (ExceptionInterface $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
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
