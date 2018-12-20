<?php

namespace Oro\Bundle\PayPalBundle\Tests\Behat\Mock\Method;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PayPalExpressCheckoutPaymentMethodMock extends PayPalExpressCheckoutPaymentMethod
{
    const SUCCESS_REDIRECT_ROUTE = 'oro_payment_callback_return';
    const PAYERID = '3JPYGZMJXAEXE';

    /**
     * {@inheritdoc}
     */
    protected function purchase(PaymentTransaction $paymentTransaction)
    {
        return array_merge(
            parent::purchase($paymentTransaction),
            [
                'purchaseRedirectUrl' => $this->generateUrl(
                    self::SUCCESS_REDIRECT_ROUTE,
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                        'token' => $paymentTransaction->getReference(),
                        'PayerID' => self::PAYERID,
                    ]
                )
            ]
        );
    }

    /**
     * @param string $route
     * @param array $params
     * @return string
     */
    private function generateUrl(string $route, array $params)
    {
        return $this->router->generate(
            $route,
            $params,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
