<?php

namespace OroB2B\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\TaxBundle\Entity\AccountGroupTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Repository\AccountGroupTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Form\Type\AccountGroupTaxCodeAutocompleteType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupType;

class AccountGroupTaxExtension extends AbstractTaxExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AccountGroupType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function addTaxCodeField(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'taxCode',
                AccountGroupTaxCodeAutocompleteType::NAME,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'orob2b.tax.taxcode.label',
                    'create_form_route' => null,
                ]
            );
    }

    /**
     * @param AccountGroup $accountGroup
     * @param AccountGroupTaxCode|AbstractTaxCode $taxCode
     * @param AccountGroupTaxCode|AbstractTaxCode $taxCodeNew
     */
    protected function handleTaxCode(
        $accountGroup,
        AbstractTaxCode $taxCode = null,
        AbstractTaxCode $taxCodeNew = null
    ) {
        if ($taxCode) {
            $taxCode->removeAccountGroup($accountGroup);
        }

        if ($taxCodeNew) {
            $taxCodeNew->addAccountGroup($accountGroup);
        }
    }

    /**
     * @param AccountGroup $object
     * @return AccountGroupTaxCode
     */
    protected function getTaxCode($object)
    {
        /** @var AccountGroupTaxCodeRepository $repository */
        $repository = $this->getRepository();

        return $repository->findOneByAccountGroup($object);
    }
}
