<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\TaxBundle\Entity\AccountTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository;
use Oro\Bundle\TaxBundle\Form\Type\AccountTaxCodeAutocompleteType;
use Oro\Bundle\AccountBundle\Form\Type\AccountGroupType;

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
                AccountTaxCodeAutocompleteType::NAME,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'oro.tax.taxcode.label',
                    'create_form_route' => null,
                ]
            );
    }

    /**
     * @param AccountGroup $accountGroup
     * @param AccountTaxCode|AbstractTaxCode $taxCode
     * @param AccountTaxCode|AbstractTaxCode $taxCodeNew
     */
    protected function handleTaxCode($accountGroup, AbstractTaxCode $taxCode = null, AbstractTaxCode $taxCodeNew = null)
    {
        if ($taxCode) {
            $taxCode->removeAccountGroup($accountGroup);
        }

        if ($taxCodeNew) {
            $taxCodeNew->addAccountGroup($accountGroup);
        }
    }

    /**
     * @param AccountGroup $object
     * @return AccountTaxCode|null
     */
    protected function getTaxCode($object)
    {
        /** @var AccountTaxCodeRepository $repository */
        $repository = $this->getRepository();

        return $repository->findOneByAccountGroup($object);
    }
}
