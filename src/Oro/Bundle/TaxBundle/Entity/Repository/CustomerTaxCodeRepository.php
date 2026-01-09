<?php

namespace Oro\Bundle\TaxBundle\Entity\Repository;

/**
 * Doctrine repository for CustomerTaxCode entity.
 *
 * Provides data access methods for customer tax codes used in tax calculations.
 * Customer tax codes are assigned to customers and customer groups to determine
 * which tax rules apply when calculating taxes for orders.
 *
 * @see \Oro\Bundle\TaxBundle\Entity\CustomerTaxCode
 */
class CustomerTaxCodeRepository extends AbstractTaxCodeRepository
{
}
