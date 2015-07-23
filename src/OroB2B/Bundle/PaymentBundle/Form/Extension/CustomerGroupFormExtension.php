<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Extension;

use Symfony\Component\Form\FormEvent;

use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerGroupType;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class CustomerGroupFormExtension extends AbstractPaymentTermExtension
{
    /**
     * {@inheritdoc}
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var CustomerGroup|null $customerGroup */
        $customerGroup = $event->getData();
        if (!$customerGroup || !$customerGroup->getId()) {
            return;
        }

        $paymentTermByCustomerGroup = $this->getPaymentTermRepository()->getOnePaymentTermByCustomerGroup($customerGroup);

        $event->getForm()->get('paymentTerm')->setData($paymentTermByCustomerGroup);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var CustomerGroup|null $customerGroup */
        $customerGroup = $event->getData();
        if (!$customerGroup || !$customerGroup->getId()) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var PaymentTerm|null  */
        $paymentTerm = $form->get('paymentTerm')->getData();

        $this->getPaymentTermRepository()->setPaymentTermToCustomerGroup($customerGroup, $paymentTerm);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CustomerGroupType::NAME;
    }
}
