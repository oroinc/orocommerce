<?php

namespace Oro\Bundle\ProductBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Prevents editing and removal of segments that represent a product collection content variant.
 */
class ProductCollectionSegmentVoter extends AbstractEntityVoter implements ServiceSubscriberInterface
{
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::EDIT, BasicPermission::DELETE];

    private ContainerInterface $container;

    private array $segmentsStateToContentVariants = [];

    public function __construct(DoctrineHelper $doctrineHelper, ContainerInterface $container)
    {
        parent::__construct($doctrineHelper);
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_product.provider.content_variant_segment_provider' => ContentVariantSegmentProvider::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->isSegmentAttachedToContentVariant($identifier)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    private function isSegmentAttachedToContentVariant(int $segmentId): bool
    {
        if (empty($this->segmentsStateToContentVariants[$segmentId])) {
            /** @var Segment $segment */
            $segment = $this->doctrineHelper->getEntityReference($this->className, $segmentId);
            $hasContentVariant = $this->getContentVariantSegmentProvider()->hasContentVariant($segment);
            $this->segmentsStateToContentVariants[$segmentId] = $hasContentVariant;
        }

        return $this->segmentsStateToContentVariants[$segmentId];
    }

    private function getContentVariantSegmentProvider(): ContentVariantSegmentProvider
    {
        return $this->container->get('oro_product.provider.content_variant_segment_provider');
    }
}
