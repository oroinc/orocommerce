<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;

class ProductKitItemLineItemsAwareStub extends ProductLineItem implements ProductKitItemLineItemsAwareInterface
{
    private Collection $kitItemLineItems;

    private string $checksum = '';

    public function __construct($identifier)
    {
        parent::__construct($identifier);

        $this->kitItemLineItems = new ArrayCollection();
    }

    public function getKitItemLineItems(): Collection
    {
        return $this->kitItemLineItems;
    }

    public function addKitItemLineItem(ProductKitItemLineItemInterface $productKitItemLineItem): self
    {
        if (!$this->kitItemLineItems->contains($productKitItemLineItem)) {
            $productKitItemLineItem->setLineItem($this);
            $this->kitItemLineItems->add($productKitItemLineItem);
        }

        return $this;
    }

    public function removeKitItemLineItem(ProductKitItemLineItemInterface $productKitItemLineItem): self
    {
        $this->kitItemLineItems->removeElement($productKitItemLineItem);

        return $this;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }
}
