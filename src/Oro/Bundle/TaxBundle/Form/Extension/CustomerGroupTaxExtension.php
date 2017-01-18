<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository;
use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeAutocompleteType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerGroupType;

class CustomerGroupTaxExtension extends AbstractTaxExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CustomerGroupType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function addTaxCodeField(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'taxCode',
                CustomerTaxCodeAutocompleteType::NAME,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'oro.tax.taxcode.label',
                    'create_form_route' => null,
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
        if ($taxCode) {
            $taxCode->removeCustomerGroup($customerGroup);
        }

        if ($taxCodeNew) {
            $taxCodeNew->addCustomerGroup($customerGroup);
        }
    }

    /**
     * @param CustomerGroup $object
     * @return CustomerTaxCode|null
     */
    protected function getTaxCode($object)
    {
        /** @var CustomerTaxCodeRepository $repository */
        $repository = $this->getRepository();

        return $repository->findOneByCustomerGroup($object);
    }
}
