<?php

namespace Oro\Bundle\TaxBundle\Model;

/**
 * Defines the contract for tax code entities.
 *
 * Tax codes are used to categorize customers, customer groups, and products for tax purposes.
 * They serve as one of the key matching criteria when determining which tax rules apply during tax calculation.
 * Implementations of this interface must provide a code identifier and a type that indicates
 * whether the code applies to customers or products.
 */
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
