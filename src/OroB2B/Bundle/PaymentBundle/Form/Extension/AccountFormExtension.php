<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountType;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class AccountFormExtension extends AbstractPaymentTermExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $account = $builder->getData();

        if ($account->getGroup()) {
            $paymentTermByAccountGroup = $this->getPaymentTermRepository()
                ->getOnePaymentTermByAccountGroup($account->getGroup());

            if ($paymentTermByAccountGroup) {
                $placeholder = $this->translator->trans(
                    'orob2b.payment.account.payment_term_defined_in_group',
                    [
                        '{{ payment_term }}' => $paymentTermByAccountGroup->getLabel()
                    ]
                );
            } else {
                $placeholder = $this->translator->trans('orob2b.payment.account.payment_term_non_defined_in_group');
            }

            $options['paymentTermOptions']['configs']['placeholder'] = $placeholder;
        }

        parent::buildForm($builder, $options);
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
