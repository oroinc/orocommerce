<?php

namespace Oro\Bundle\ProductBundle\EventListener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;

/**
 * Trigger product reindexation on variant link changes
 * @codeCoverageIgnore No logic here
 */
class ProductVariantsChangedListener
{
    /** @var ProductReindexManager */
    private $productReindexManager;

    public function __construct(ProductReindexManager $productReindexManager)
    {
        $this->productReindexManager = $productReindexManager;
    }

    /** @ORM\PrePersist()
     *
     */
    public function prePersist(ProductVariantLink $productVariantLink, LifecycleEventArgs $event)
    {
        if ($productVariantLink->getProduct()) {
            $this->productReindexManager->reindexProduct($productVariantLink->getProduct());
        }
    }

    /** @ORM\PreRemove()
     *
     */
    public function preRemove(ProductVariantLink $productVariantLink)
    {
        if ($productVariantLink->getProduct()) {
            $this->productReindexManager->reindexProduct($productVariantLink->getProduct());
        }
    }
}
