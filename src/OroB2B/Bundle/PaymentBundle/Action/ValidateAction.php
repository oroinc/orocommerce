<?php

namespace OroB2B\Bundle\PaymentBundle\Action;

use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class ValidateAction extends AbstractPaymentMethodAction
{
    const AUTHORIZE_AMOUNT = 0;

    /** {@inheritdoc} */
    protected function configureOptionsResolver(OptionsResolver $resolver)
    {
        parent::configureOptionsResolver($resolver);

        $resolver
            ->remove('amount')
            ->setRequired('paymentMethod')
            ->addAllowedTypes('paymentMethod', ['string', 'Symfony\Component\PropertyAccess\PropertyPathInterface']);
    }

    /** {@inheritdoc} */
    protected function configureValuesResolver(OptionsResolver $resolver)
    {
        parent::configureValuesResolver($resolver);

        $resolver
            ->remove('amount')
            ->setRequired('paymentMethod')
            ->addAllowedTypes('paymentMethod', 'string');
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

        $validatePaymentTransaction
            ->setAmount(self::AUTHORIZE_AMOUNT)
            ->setCurrency($options['currency']);

        if (!empty($options['transactionOptions'])) {
            $validatePaymentTransaction->setTransactionOptions($options['transactionOptions']);
        }

        $response = [];
        try {
            $response = $this->paymentMethodRegistry
                ->getPaymentMethod($validatePaymentTransaction->getPaymentMethod())
                ->execute($validatePaymentTransaction);
        } catch (\Exception $e) {
        }

        $this->paymentTransactionProvider->savePaymentTransaction($validatePaymentTransaction);

        $this->setAttributeValue(
            $context,
            array_merge(
                [
                    'paymentMethod' => $options['paymentMethod'],
                    'errorUrl' => $this->router->generate(
                        'orob2b_payment_callback_error',
                        [
                            'accessIdentifier' => $validatePaymentTransaction->getAccessIdentifier(),
                            'accessToken' => $validatePaymentTransaction->getAccessToken(),
                        ],
                        true
                    ),
                    'returnUrl' => $this->router->generate(
                        'orob2b_payment_callback_return',
                        [
                            'accessIdentifier' => $validatePaymentTransaction->getAccessIdentifier(),
                            'accessToken' => $validatePaymentTransaction->getAccessToken(),
                        ],
                        true
                    ),
                ],
                $response
            )
        );
    }
}
