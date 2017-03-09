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

    /**
     * {@inheritDoc}
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        return [
            'purchaseRedirectUrl' => self::TEST_URL,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        return static::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(PaymentContextInterface $context)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($actionName)
    {
        return self::PURCHASE === $actionName;
    }
}
