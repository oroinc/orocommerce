<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\SaleBundle\Entity\Quote;

class QuoteStub extends Quote
{
    /** @var AbstractEnumValue */
    protected $internalStatus;

    /**
     * @return AbstractEnumValue
     */
    public function getInternalStatus()
    {
        return $this->internalStatus;
    }

    /**
     * @param AbstractEnumValue $internalStatus
     */
    public function setInternalStatus(AbstractEnumValue $internalStatus = null)
    {
        $this->internalStatus = $internalStatus;
    }
}
