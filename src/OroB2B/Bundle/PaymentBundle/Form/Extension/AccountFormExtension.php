<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountType;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermSelectType;

class AccountFormExtension extends AbstractPaymentTermExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $account = $builder->getData();
        $customOptions = [
            'label' => 'orob2b.payment.paymentterm.entity_label',
            'required' => false,
            'mapped' => false,
        ];

        if ($account->getGroup()) {
            $paymentTermByAccountGroup =
                $this->getPaymentTermRepository()->getOnePaymentTermByAccountGroup($account->getGroup());

            if ($paymentTermByAccountGroup) {
                $customOptions['configs']['placeholder'] = $this->translator->trans(
                    'orob2b.payment.account.payment_term_defined_in_group',
                    [
                        '%payment_term%' => $paymentTermByAccountGroup->getLabel()
                    ]
                );
            } else {
                $customOptions['configs']['placeholder'] = $this->translator->trans(
                    'orob2b.payment.account.payment_term_non_defined_in_group'
                );
            }
        }

        $builder->add(
            'paymentTerm',
            PaymentTermSelectType::NAME,
            $customOptions
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

        $form = $event->getForm();
        if (!$account || !$account->getId()) {
            return;
        }

        $paymentTerm = $this->getPaymentTermRepository()->getOnePaymentTermByAccount($account);
        $event->getForm()->get('paymentTerm')->setData($paymentTerm);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var Account|null $account */
        $account = $event->getData();
        if (!$account || !$account->getId()) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var PaymentTerm|null $paymentTerm */
        $paymentTerm = $form->get('paymentTerm')->getData();

        $this->getPaymentTermRepository()->setPaymentTermToAccount($account, $paymentTerm);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AccountType::NAME;
    }
}
