<?php

namespace Oro\Bundle\PromotionBundle\Normalizer;

/**
 * Defines the interface of normalizers which will be used for Promotion normalization.
 */
interface NormalizerInterface
{
    /**
     * @param object $object
     * @return array
     */
    public function normalize($object);

    /**
     * @param array $objectData
     * @return object
     */
    public function denormalize(array $objectData);
}
