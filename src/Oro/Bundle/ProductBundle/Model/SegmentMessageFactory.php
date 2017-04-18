<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Factory for creating MQ messages with segment and website ids, and getting data from them.
 */
class SegmentMessageFactory
{
    const ID = 'id';
    const WEBSITE_IDS = 'website_ids';

    /**
     * @var OptionsResolver
     */
    private $resolver;

    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @var SegmentRepository
     */
    private $segmentRepository;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Segment $segment
     * @param array $websiteIds
     * @return array
     */
    public function createMessage(Segment $segment, array $websiteIds)
    {
        return $this->getResolvedData([
            self::ID => $segment->getId(),
            self::WEBSITE_IDS => $websiteIds,
        ]);
    }

    /**
     * @param array $data
     * @return Segment
     * @throws InvalidArgumentException
     */
    public function getSegmentFromMessage($data)
    {
        $data = $this->getResolvedData($data);

        $segment = $this->getSegmentRepository()->find($data[self::ID]);
        if (!$segment) {
            throw new InvalidArgumentException(sprintf('No segment exists with id "%d"', $data[self::ID]));
        }

        return $segment;
    }

    public function getWebsiteIdsFromMessage($data)
    {
        $data = $this->getResolvedData($data);

        return $data[self::WEBSITE_IDS];
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionsResolver()
    {
        if (null === $this->resolver) {
            $resolver = new OptionsResolver();
            $resolver->setRequired([
                self::ID,
                self::WEBSITE_IDS,
            ]);

            $resolver->setAllowedTypes(self::ID, 'int');
            $resolver->setAllowedTypes(self::WEBSITE_IDS, 'array');

            $this->resolver = $resolver;
        }

        return $this->resolver;
    }

    /**
     * @param array $data
     * @return array
     * @throws InvalidArgumentException
     */
    private function getResolvedData($data)
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
    private function getSegmentRepository()
    {
        if (!$this->segmentRepository) {
            $this->segmentRepository = $this->registry->getRepository(Segment::class);
        }

        return $this->segmentRepository;
    }
}
