<?php

namespace Oro\Bundle\PaymentBundle\Action;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Action to validate a payment.
 */
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
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $this->extractPaymentMethodFromOptions($options);

        $validatePaymentTransaction = $this->paymentTransactionProvider->createPaymentTransaction(
            $paymentMethod->getIdentifier(),
            PaymentMethodInterface::VALIDATE,
            $options['object']
        );

        if (!empty($options['transactionOptions'])) {
            $validatePaymentTransaction->setTransactionOptions($options['transactionOptions']);
        }

        $response = $this->executePaymentTransaction($validatePaymentTransaction, $paymentMethod);

        $this->paymentTransactionProvider->savePaymentTransaction($validatePaymentTransaction);

        $this->setAttributeValue(
            $context,
            array_merge(
                ['paymentMethod' => $paymentMethod->getIdentifier()],
                $this->getCallbackUrls($validatePaymentTransaction),
                $validatePaymentTransaction->getTransactionOptions(),
                $response
            )
        );
    }
}
