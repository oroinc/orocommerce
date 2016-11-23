<?php

namespace Oro\Bundle\PaymentBundle\Form\Extension;

use Symfony\Component\Form\FormEvent;

use Oro\Bundle\CustomerBundle\Form\Type\AccountGroupType;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;

class AccountGroupFormExtension extends AbstractPaymentTermExtension
{
    /**
     * {@inheritdoc}
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var AccountGroup|null $accountGroup */
        $accountGroup = $event->getData();
        if (!$accountGroup) {
            return;
        }

        $paymentTermByAccountGroup = $this->getPaymentTermRepository()->getOnePaymentTermByAccountGroup($accountGroup);

        $event->getForm()->get('paymentTerm')->setData($paymentTermByAccountGroup);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var AccountGroup|null $accountGroup */
        $accountGroup = $event->getData();
        if (!$accountGroup) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var PaymentTerm|null  */
        $paymentTerm = $form->get('paymentTerm')->getData();

        $this->getPaymentTermRepository()->setPaymentTermToAccountGroup($accountGroup, $paymentTerm);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AccountGroupType::NAME;
    }
}
