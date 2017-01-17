<?php

namespace Oro\Bundle\TaxBundle\Model;

interface TaxCodeInterface
{
    const TYPE_ACCOUNT = 'customer';
    const TYPE_ACCOUNT_GROUP = 'customer_group';

    const TYPE_PRODUCT = 'product';

    /**
     * @return string
     */
    public function getCode();

    /**
     * @return string
     */
    public function getType();
}
