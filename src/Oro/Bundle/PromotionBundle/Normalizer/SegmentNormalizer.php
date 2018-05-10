<?php

namespace Oro\Bundle\PromotionBundle\Normalizer;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This class convert promotion Segment entity to array and vice versa
 */
class SegmentNormalizer implements NormalizerInterface
{
    const REQUIRED_OPTIONS = [
        'definition'
    ];

    /**
     * @param Segment $segment
     * @return array
     */
    public function normalize($segment)
    {
        if (!$segment instanceof Segment) {
            throw new \InvalidArgumentException('Argument segment should be instance of Segment entity');
        }

        return [
            'definition' => $segment->getDefinition()
        ];
    }

    /**
     * @param array $segmentData
     * @return Segment
     */
    public function denormalize(array $segmentData)
    {
        $resolver = $this->getOptionResolver();
        $resolver->resolve($segmentData);

        $segment = new Segment();
        $segment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC))
            ->setDefinition($segmentData['definition'])
            ->setEntity(Product::class);

        return $segment;
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(self::REQUIRED_OPTIONS);

        $resolver->setAllowedTypes('definition', ['string']);

        return $resolver;
    }
}
