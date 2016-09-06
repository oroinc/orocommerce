<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Migrations\Schema\v1_2;

use Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_2\MoveCheckoutAddressDataQuery as BaseQuery;

class MoveCheckoutAddressDataQuery extends BaseQuery
{
    /**
     * {@inheritdoc}
     */
    protected function getSourceTableName()
    {
        return 'orob2b_alternative_checkout';
    }

    /**
     * {@inheritdoc}
     */
    protected function getBaseTableName()
    {
        return 'oro_checkout';
    }
}
