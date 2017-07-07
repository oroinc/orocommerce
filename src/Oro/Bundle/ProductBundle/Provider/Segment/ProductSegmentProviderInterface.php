<?php

namespace Oro\Bundle\ProductBundle\Provider\Segment;

use Oro\Bundle\SegmentBundle\Entity\Segment;

interface ProductSegmentProviderInterface
{
    /**
     * @param int $segmentId
     *
     * @return Segment|null
     */
    public function getProductSegmentById($segmentId);
}
