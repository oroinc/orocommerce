<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\SaleBundle\Entity\Quote;

class QuoteStub extends Quote
{
    /** @var EnumOptionInterface */
    protected $internalStatus;

    /**
     * @return EnumOptionInterface
     */
    public function getInternalStatus()
    {
        return $this->internalStatus;
    }

    public function setInternalStatus(EnumOptionInterface $internalStatus = null)
    {
        $this->internalStatus = $internalStatus;
    }
}
