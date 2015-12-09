<?php

namespace OroB2B\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountType;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Form\Type\AccountTaxCodeAutocompleteType;

class AccountTaxExtension extends AbstractTypeExtension
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'taxCode',
                AccountTaxCodeAutocompleteType::NAME,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'orob2b.tax.accounttaxcode.entity_label'
                ]
            );
        
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }
    /**
     * {@inheritdoc}
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var Account|null $account */
        $account = $event->getData();
        if (!$account || !$account->getId()) {
            return;
        }

        $taxCode = $this->getAccountTaxCode($account);

        $event->getForm()->get('taxCode')->setData($taxCode);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var Account|null $account */
        $account = $event->getData();
        if (!$account) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        $entityManager = $this->doctrineHelper->getEntityManager('OroB2BTaxBundle:AccountTaxCode');

        $taxCodeData = (array)$form->get('taxCode')->getData();
        $taxCode = $this->getAccountTaxCode($account);

        if ($taxCodeData) {
            if (!$taxCode) {
                $taxCode = new AccountTaxCode;
            }
            $taxCode->setAccount($account);
            $entityManager->persist($taxCode);
        } else {
            $entityManager->remove($taxCode);
        }
    }

    /**
     * @param Account $account
     * @return AccountTaxCode|null
     */
    protected function getAccountTaxCode($account)
    {
        /** @var AccountTaxCodeRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository('OroB2BTaxBundle:AccountTaxCode');

        return $repository->findOneByAccount($account);
    }
}