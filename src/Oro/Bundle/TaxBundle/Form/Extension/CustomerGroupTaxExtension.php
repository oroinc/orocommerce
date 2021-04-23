<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerGroupType;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeAutocompleteType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Stands for handling tax code for Customer Group.
 */
class CustomerGroupTaxExtension extends AbstractTaxExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [CustomerGroupType::class];
    }

    /**
     * {@inheritdoc}
     */
    protected function addTaxCodeField(FormBuilderInterface $builder)
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

    /**
     * @param CustomerGroup $customerGroup
     * @param CustomerTaxCode|AbstractTaxCode $taxCode
     * @param CustomerTaxCode|AbstractTaxCode $taxCodeNew
     */
    protected function handleTaxCode(
        $customerGroup,
        AbstractTaxCode $taxCode = null,
        AbstractTaxCode $taxCodeNew = null
    ) {
        $customerGroup->setTaxCode($taxCodeNew);
    }

    /**
     * @param CustomerGroup $object
     * @return CustomerTaxCode|null
     */
    protected function getTaxCode($object)
    {
        return $object->getTaxCode();
    }
}
