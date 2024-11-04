<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\Stub\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class ExpressCheckoutMethodStub implements PaymentMethodInterface
{
    const TYPE = 'test_express_checkout';

    /** @internal */
    const TEST_URL = '/';

    #[\Override]
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        return [
            'purchaseRedirectUrl' => self::TEST_URL,
        ];
    }

    #[\Override]
    public function getIdentifier()
    {
        return static::TYPE;
    }

    #[\Override]
    public function isApplicable(PaymentContextInterface $context)
    {
        return true;
    }

    #[\Override]
    public function supports($actionName)
    {
        return self::PURCHASE === $actionName;
    }
}
