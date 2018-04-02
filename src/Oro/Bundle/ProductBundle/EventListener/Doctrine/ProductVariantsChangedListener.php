<?php

namespace Oro\Bundle\ProductBundle\EventListener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;

/**
 * @codeCoverageIgnore No logic here
 */
class ProductVariantsChangedListener
{
    /** @var ProductReindexManager */
    private $productReindexManager;

    /**
     * @param ProductReindexManager $productReindexManager
     */
    public function __construct(ProductReindexManager $productReindexManager)
    {
        $this->productReindexManager = $productReindexManager;
    }

    /** @ORM\PrePersist()
     *
     * @param ProductVariantLink $productVariantLink
     * @param LifecycleEventArgs $event
     */
    public function prePersist(ProductVariantLink $productVariantLink, LifecycleEventArgs $event)
    {
        $this->productReindexManager->reindexProduct($productVariantLink->getProduct());
    }

    /** @ORM\PreRemove()
     *
     * @param ProductVariantLink $productVariantLink
     */
    public function preRemove(ProductVariantLink $productVariantLink)
    {
        $this->productReindexManager->reindexProduct($productVariantLink->getProduct());
    }
}
