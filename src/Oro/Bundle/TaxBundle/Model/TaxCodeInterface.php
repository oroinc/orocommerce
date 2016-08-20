<?php

namespace Oro\Bundle\TaxBundle\Model;

interface TaxCodeInterface
{
    const TYPE_ACCOUNT = 'account';
    const TYPE_ACCOUNT_GROUP = 'account_group';

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
