<?php

namespace OroB2B\Bundle\PaymentBundle\Action;

use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

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
    protected function configureValuesResolver(OptionsResolver $resolver)
    {
        parent::configureValuesResolver($resolver);

        $resolver
            ->setRequired('paymentMethod')
            ->addAllowedTypes('paymentMethod', 'string');
    }

    /** {@inheritdoc} */
    protected function executeAction($context)
    {
        $options = $this->getOptions($context);

        $paymentTransaction = $this->paymentTransactionProvider->createPaymentTransaction(
            $options['paymentMethod'],
            PaymentMethodInterface::PURCHASE,
            $options['object']
        );

        $paymentTransaction
            ->setAmount($options['amount'])
            ->setCurrency($options['currency']);

        $this->paymentMethodRegistry
            ->getPaymentMethod($options['paymentMethod'])
            ->execute($paymentTransaction);

        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
    }
}
