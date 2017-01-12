<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerType;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository;
use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeAutocompleteType;

class CustomerTaxExtension extends AbstractTaxExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CustomerType::NAME;
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
     * @param Customer $customer
     * @param CustomerTaxCode|AbstractTaxCode $taxCode
     * @param CustomerTaxCode|AbstractTaxCode $taxCodeNew
     */
    protected function handleTaxCode($customer, AbstractTaxCode $taxCode = null, AbstractTaxCode $taxCodeNew = null)
    {
        if ($taxCode) {
            $taxCode->removeCustomer($customer);
        }

        if ($taxCodeNew) {
            $taxCodeNew->addCustomer($customer);
        }
    }

    /**
     * @param Customer $object
     * @return CustomerTaxCode|null
     */
    protected function getTaxCode($object)
    {
        /** @var CustomerTaxCodeRepository $repository */
        $repository = $this->getRepository();

        return $repository->findOneByCustomer($object);
    }
}
