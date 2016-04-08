<?php

namespace OroB2B\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountType;
use OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Form\Type\AccountTaxCodeAutocompleteType;

class AccountTaxExtension extends AbstractTaxExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AccountType::NAME;
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
                    'label' => 'orob2b.tax.taxcode.label',
                    'create_form_route' => null,
                ]
            );
    }

    /**
     * @param Account $account
     * @param AccountTaxCode|AbstractTaxCode $taxCode
     * @param AccountTaxCode|AbstractTaxCode $taxCodeNew
     */
    protected function handleTaxCode($account, AbstractTaxCode $taxCode = null, AbstractTaxCode $taxCodeNew = null)
    {
        if ($taxCode) {
            $taxCode->removeAccount($account);
        }

        if ($taxCodeNew) {
            $taxCodeNew->addAccount($account);
        }
    }

    /**
     * @param Account $object
     * @return AccountTaxCode|null
     */
    protected function getTaxCode($object)
    {
        /** @var AccountTaxCodeRepository $repository */
        $repository = $this->getRepository();

        return $repository->findOneByAccount($object);
    }
}
