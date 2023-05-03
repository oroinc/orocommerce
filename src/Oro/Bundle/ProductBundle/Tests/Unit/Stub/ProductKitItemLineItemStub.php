<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Stub;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

class ProductKitItemLineItemStub extends ProductLineItem implements ProductKitItemLineItemInterface
{
    private ?ProductLineItemInterface $lineItem;

    private ?ProductKitItem $kitItem;

    private int $sortOrder = 0;

    public function __construct($identifier)
    {
        parent::__construct($identifier);
    }

    public function getLineItem(): ?ProductLineItemInterface
    {
        return $this->lineItem;
    }

    public function setLineItem(?ProductLineItemInterface $lineItem): self
    {
        $this->lineItem = $lineItem;

        return $this;
    }

    public function getKitItem(): ?ProductKitItem
    {
        return $this->kitItem;
    }

    public function setKitItem(?ProductKitItem $kitItem): self
    {
        $this->kitItem = $kitItem;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
}
