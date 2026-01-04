<?php

namespace Oro\Bundle\TaxBundle\Model;

interface TaxCodeInterface
{
    public const TYPE_ACCOUNT = 'customer';
    public const TYPE_ACCOUNT_GROUP = 'customer_group';

    public const TYPE_PRODUCT = 'product';

    /**
     * @return string
     */
    public function getCode();

    /**
     * @return string
     */
    public function getType();
}
