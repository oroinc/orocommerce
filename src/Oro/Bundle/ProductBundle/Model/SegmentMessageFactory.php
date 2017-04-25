<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
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
    const DEFINITION = 'definition';

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
     * @param array $websiteIds
     * @param string|null $definition
     * @param Segment|null $segment
     * @return array
     */
    public function createMessage(array $websiteIds, Segment $segment = null, $definition = null)
    {
        return $this->getResolvedData([
            self::ID => $segment ? $segment->getId() : null,
            self::WEBSITE_IDS => $websiteIds,
            self::DEFINITION => $definition
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
    public function getWebsiteIdsFromMessage(array $data)
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
                self::WEBSITE_IDS
            ]);

            $resolver->setDefined([
                self::ID,
                self::DEFINITION
            ]);

            $resolver->setAllowedTypes(self::WEBSITE_IDS, 'array');
            $resolver->setAllowedTypes(self::ID, ['null','int']);
            $resolver->setAllowedTypes(self::DEFINITION, ['null', 'string']);

            $this->resolver = $resolver;
        }

        return $this->resolver;
    }

    /**
     * @param array $data
     * @return array
     * @throws InvalidArgumentException
     */
    private function getResolvedData(array $data)
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
