<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class AuthorizeRequest extends AbstractRequest
{
    /** {@inheritdoc} */
    public function getTransactionType()
    {
        return Option\Transaction::AUTHORIZE;
    }
}
