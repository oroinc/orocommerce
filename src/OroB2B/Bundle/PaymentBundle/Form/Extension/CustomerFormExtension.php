<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerType;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermSelectType;

class CustomerFormExtension extends AbstractPaymentTermExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $customer = $builder->getData();
        $customOptions = [
            'label' => 'orob2b.payment.paymentterm.entity_label',
            'required' => false,
            'mapped' => false,
        ];

        if ($customer->getGroup()) {
            $paymentTermByCustomerGroup =
                $this->getPaymentTermRepository()->getOnePaymentTermByCustomerGroup($customer->getGroup());

            if ($paymentTermByCustomerGroup) {
                $customOptions['configs']['placeholder'] = $this->translator->trans(
                    'orob2b.payment.customer.payment_term_defined_in_group',
                    [
                        '%payment_term%' => $paymentTermByCustomerGroup->getLabel()
                    ]
                );
            } else {
                $customOptions['configs']['placeholder'] = $this->translator->trans(
                    'orob2b.payment.customer.payment_term_non_defined_in_group'
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
        /** @var Customer|null $customer */
        $customer = $event->getData();

        $form = $event->getForm();
        if (!$customer || !$customer->getId()) {
            return;
        }

        $paymentTerm = $this->getPaymentTermRepository()->getOnePaymentTermByCustomer($customer);
        $event->getForm()->get('paymentTerm')->setData($paymentTerm);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var Customer|null $customer */
        $customer = $event->getData();
        if (!$customer || !$customer->getId()) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var PaymentTerm|null $paymentTerm */
        $paymentTerm = $form->get('paymentTerm')->getData();

        $this->getPaymentTermRepository()->setPaymentTermToCustomer($customer, $paymentTerm);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CustomerType::NAME;
    }
}
