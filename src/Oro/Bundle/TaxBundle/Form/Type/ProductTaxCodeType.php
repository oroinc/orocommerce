<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

/**
 * Form type for creating and editing product tax codes.
 *
 * Product tax codes are used to categorize products for tax purposes. They are assigned to products and are matched
 * with customer tax codes and jurisdictions to determine which tax rules apply to line items during checkout.
 *
 * @see \Oro\Bundle\TaxBundle\Entity\ProductTaxCode
 */
class ProductTaxCodeType extends AbstractTaxCodeType
{
    public const NAME = 'oro_tax_product_tax_code_type';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
