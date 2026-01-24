<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

/**
 * Form type for creating and editing customer tax codes.
 *
 * Customer tax codes are used to categorize customers for tax purposes.
 * They are assigned to customers and customer groups and are matched with product tax codes and jurisdictions
 * to determine which tax rules apply during checkout.
 *
 * @see \Oro\Bundle\TaxBundle\Entity\CustomerTaxCode
 */
class CustomerTaxCodeType extends AbstractTaxCodeType
{
    const NAME = 'oro_tax_customer_tax_code_type';

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
