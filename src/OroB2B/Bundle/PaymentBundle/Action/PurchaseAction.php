<?php

namespace OroB2B\Bundle\PaymentBundle\Action;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Model\AmountAwareInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurchaseAction extends AbstractPaymentMethodAction
{
    /** {@inheritdoc} */
    protected function configureOptionsResolver(OptionsResolver $resolver)
    {
        parent::configureOptionsResolver($resolver);

        $resolver
            ->setRequired('paymentMethod')
            ->addAllowedTypes('paymentMethod', 'string');
    }

    /** {@inheritdoc} */
    protected function executeAction($context)
    {
        $object = $this->contextAccessor->getValue($context, $this->options['object']);
        if (!$object instanceof AmountAwareInterface) {
            return;
        }

        $paymentMethod = $this->contextAccessor->getValue($context, $this->options['paymentMethod']);
        if (!$paymentMethod) {
            return;
        }

        $paymentTransaction = $this->paymentTransactionProvider->createPaymentTransaction(
            $paymentMethod,
            PaymentMethodInterface::PURCHASE,
            $object
        );

        $paymentTransaction
            ->setAmount($object->getAmount())
            ->setCurrency($object->getCurrency());

        $this->paymentMethodRegistry
            ->getPaymentMethod($paymentMethod)
            ->execute($paymentTransaction);

        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
    }
}
