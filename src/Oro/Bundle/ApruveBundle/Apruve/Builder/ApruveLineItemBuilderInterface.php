<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder;

use Oro\Bundle\ApruveBundle\Apruve\Request\LineItem\ApruveLineItemRequestDataInterface;

interface ApruveLineItemBuilderInterface
{
    /**
     * @return ApruveLineItemRequestDataInterface
     */
    public function getResult();

    /**
     * @param string $notes
     *
     * @return self
     */
    public function setMerchantNotes($notes);

    /**
     * @param string $vendor
     *
     * @return self
     */
    public function setVendor($vendor);

    /**
     * @param int|float|string $amount
     *
     * @return self
     */
    public function setAmountEa($amount);
}
