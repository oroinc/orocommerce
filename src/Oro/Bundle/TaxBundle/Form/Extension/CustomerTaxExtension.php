<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerType;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeAutocompleteType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Handles tax code for Customer.
 */
class CustomerTaxExtension extends AbstractTaxExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [CustomerType::class];
    }

    #[\Override]
    protected function addTaxCodeField(FormBuilderInterface $builder): void
    {
        $builder
            ->add(
                'taxCode',
                CustomerTaxCodeAutocompleteType::class,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'oro.tax.taxcode.label',
                    'create_form_route' => null,
                    'dynamic_fields_ignore_exception' => true,
                ]
            );
    }

    #[\Override]
    protected function handleTaxCode(object $entity, ?AbstractTaxCode $taxCode, ?AbstractTaxCode $taxCodeNew): void
    {
        /** @var Customer $entity */
        /** @var CustomerTaxCode|null $taxCodeNew */
        $entity->setTaxCode($taxCodeNew);
    }

    #[\Override]
    protected function getTaxCode(object $entity): ?AbstractTaxCode
    {
        /** @var Customer $entity */
        return $entity->getTaxCode();
    }
}
