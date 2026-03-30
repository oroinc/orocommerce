<?php

declare(strict_types=1);

namespace Oro\Bundle\TaxBundle\Tests\Unit\Stub;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;

/**
 * Stub for OrderLineItem with freeFormTaxCode field.
 */
class OrderLineItemStub extends OrderLineItem
{
    private ?ProductTaxCode $freeFormTaxCode = null;

    public function getFreeFormTaxCode(): ?ProductTaxCode
    {
        return $this->freeFormTaxCode;
    }

    public function setFreeFormTaxCode(?ProductTaxCode $freeFormTaxCode): self
    {
        $this->freeFormTaxCode = $freeFormTaxCode;

        return $this;
    }
}
