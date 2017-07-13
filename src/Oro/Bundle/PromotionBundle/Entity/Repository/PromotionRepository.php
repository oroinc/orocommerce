<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class PromotionRepository extends EntityRepository
{
    /**
     * @param Segment $segment
     * @return object|Promotion|null
     */
    public function findPromotionByProductSegment(Segment $segment)
    {
        return $this->findOneBy(['productsSegment' => $segment]);
    }
}
