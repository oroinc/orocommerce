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
            ->addAllowedTypes('paymentMethod', ['string', 'Symfony\Component\PropertyAccess\PropertyPathInterface']);
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

        if (!empty($options['transactionOptions'])) {
            $paymentTransaction->setTransactionOptions($options['transactionOptions']);
        }

        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        $response = [];

        try {
            $response = $this->paymentMethodRegistry
                ->getPaymentMethod($options['paymentMethod'])
                ->execute($paymentTransaction);

            $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
        } catch (\Exception $e) {
        }

        $this->setAttributeValue(
            $context,
            array_merge(
                [
                    'paymentMethod' => $options['paymentMethod'],
                    'errorUrl' => $this->router->generate(
                        'orob2b_payment_callback_error',
                        [
                            'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                            'accessToken' => $paymentTransaction->getAccessToken(),
                        ],
                        true
                    ),
                    'returnUrl' => $this->router->generate(
                        'orob2b_payment_callback_return',
                        [
                            'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                            'accessToken' => $paymentTransaction->getAccessToken(),
                        ],
                        true
                    ),
                ],
                $response
            )
        );
    }
}
