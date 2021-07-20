<?php

namespace Oro\Bundle\ProductBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Prevents editing and removal of segments that represent a product collection content variant.
 */
class ProductCollectionSegmentVoter extends AbstractEntityVoter
{
    /**
     * @var array
     */
    protected $supportedAttributes = [BasicPermission::EDIT, BasicPermission::DELETE];

    /**
     * @var array
     */
    private $segmentsStateToContentVariants = [];

    /**
     * @var ContentVariantSegmentProvider
     */
    private $contentVariantSegmentProvider;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ContentVariantSegmentProvider $contentVariantSegmentProvider
    ) {
        $this->contentVariantSegmentProvider = $contentVariantSegmentProvider;
        parent::__construct($doctrineHelper);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->isSegmentAttachedToContentVariant($identifier)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @param int $segmentId
     * @return bool
     */
    protected function isSegmentAttachedToContentVariant($segmentId)
    {
        if (empty($this->segmentsStateToContentVariants[$segmentId])) {
            /** @var Segment $segment */
            $segment = $this->doctrineHelper->getEntityReference($this->className, $segmentId);
            $hasContentVariant = $this->contentVariantSegmentProvider->hasContentVariant($segment);
            $this->segmentsStateToContentVariants[$segmentId] = $hasContentVariant;
        }

        return $this->segmentsStateToContentVariants[$segmentId];
    }
}
