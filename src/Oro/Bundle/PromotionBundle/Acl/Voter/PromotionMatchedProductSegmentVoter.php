<?php

namespace Oro\Bundle\PromotionBundle\Acl\Voter;

use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\PromotionRepository;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Prevents editing and removal of segments that represent a list of promotions.
 */
class PromotionMatchedProductSegmentVoter extends AbstractEntityVoter
{
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::EDIT, BasicPermission::DELETE];

    private array $segmentsStateToPromotions = [];

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->isSegmentAttachedToPromotion($identifier)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    private function isSegmentAttachedToPromotion(int $segmentId): bool
    {
        if (!\array_key_exists($segmentId, $this->segmentsStateToPromotions)) {
            /** @var Segment $segment */
            $segment = $this->doctrineHelper->getEntityReference($this->className, $segmentId);

            /** @var PromotionRepository $promotionRepository */
            $promotionRepository = $this->doctrineHelper->getEntityRepository(Promotion::class);
            $this->segmentsStateToPromotions[$segmentId] = (bool)$promotionRepository
                ->findPromotionByProductSegment($segment);
        }

        return $this->segmentsStateToPromotions[$segmentId];
    }
}
