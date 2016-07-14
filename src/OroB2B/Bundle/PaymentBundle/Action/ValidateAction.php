<?php

namespace OroB2B\Bundle\PaymentBundle\Action;

use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class ValidateAction extends AbstractPaymentMethodAction
{
    /** {@inheritdoc} */
    protected function configureOptionsResolver(OptionsResolver $resolver)
    {
        parent::configureOptionsResolver($resolver);

        $resolver
            ->remove(['amount', 'currency']);
    }

    /** {@inheritdoc} */
    protected function configureValuesResolver(OptionsResolver $resolver)
    {
        parent::configureValuesResolver($resolver);

        $resolver
            ->remove(['amount', 'currency']);
    }

    /**
     * @param mixed $context
     */
    protected function executeAction($context)
    {
        $options = $this->getOptions($context);

        $validatePaymentTransaction = $this->paymentTransactionProvider->createPaymentTransaction(
            $options['paymentMethod'],
            PaymentMethodInterface::VALIDATE,
            $options['object']
        );

        if (!empty($options['transactionOptions'])) {
            $validatePaymentTransaction->setTransactionOptions($options['transactionOptions']);
        }

        $response = $this->executePaymentTransaction($validatePaymentTransaction);

        $this->paymentTransactionProvider->savePaymentTransaction($validatePaymentTransaction);

        $this->setAttributeValue(
            $context,
            array_merge(
                ['paymentMethod' => $options['paymentMethod']],
                $this->getCallbackUrls($validatePaymentTransaction),
                (array) $validatePaymentTransaction->getTransactionOptions(),
                $response
            )
        );
    }
}
