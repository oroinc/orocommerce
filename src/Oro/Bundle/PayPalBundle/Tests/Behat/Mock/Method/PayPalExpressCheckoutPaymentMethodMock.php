<?php

namespace Oro\Bundle\PayPalBundle\Tests\Behat\Mock\Method;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PayPalExpressCheckoutPaymentMethodMock extends PayPalExpressCheckoutPaymentMethod
{
    public const SUCCESS_REDIRECT_ROUTE = 'oro_payment_callback_return';
    public const PAYERID = '3JPYGZMJXAEXE';

    private ?RouterInterface $router;

    public function setRouter(RouterInterface $router): self
    {
        $this->router = $router;
        return $this;
    }

    #[\Override]
    protected function purchase(PaymentTransaction $paymentTransaction): array
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
