<?php

namespace Oro\Bundle\PaymentBundle\Action;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ValidateAction extends AbstractPaymentMethodAction
{
    #[\Override]
    protected function configureOptionsResolver(OptionsResolver $resolver)
    {
        parent::configureOptionsResolver($resolver);

        $resolver
            ->remove(['amount', 'currency']);
    }

    #[\Override]
    protected function configureValuesResolver(OptionsResolver $resolver)
    {
        parent::configureValuesResolver($resolver);

        $resolver
            ->remove(['amount', 'currency']);
    }

    /**
     * @param mixed $context
     */
    #[\Override]
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
                $validatePaymentTransaction->getTransactionOptions(),
                $response
            )
        );
    }
}
