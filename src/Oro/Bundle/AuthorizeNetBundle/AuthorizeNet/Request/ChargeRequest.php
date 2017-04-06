<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class ChargeRequest extends AbstractRequest
{
    /** {@inheritdoc} */
    public function getTransactionType()
    {
        return Option\Transaction::CHARGE;
    }
}
