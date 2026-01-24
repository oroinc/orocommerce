<?php

namespace Oro\Bundle\TaxBundle\Entity\Repository;

/**
 * Doctrine repository for ProductTaxCode entity.
 *
 * Provides data access methods for product tax codes used in tax calculations.
 * Product tax codes are assigned to products to determine which tax rules apply
 * when calculating taxes for line items in orders.
 *
 * @see \Oro\Bundle\TaxBundle\Entity\ProductTaxCode
 */
class ProductTaxCodeRepository extends AbstractTaxCodeRepository
{
}
