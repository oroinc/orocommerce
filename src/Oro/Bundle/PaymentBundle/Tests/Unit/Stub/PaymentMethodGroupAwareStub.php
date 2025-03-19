<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Stub;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodGroupAwareInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class PaymentMethodGroupAwareStub implements PaymentMethodInterface, PaymentMethodGroupAwareInterface
{
    public function __construct(private string $paymentMethodIdentifier, private string $paymentMethodGroup)
    {
    }

    #[\Override]
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        return ['successful' => true];
    }

    #[\Override]
    public function getIdentifier()
    {
        return $this->paymentMethodIdentifier;
    }

    #[\Override]
    public function isApplicable(PaymentContextInterface $context)
    {
        return true;
    }

    #[\Override]
    public function supports($actionName)
    {
        return true;
    }

    #[\Override]
    public function isApplicableForGroup(string $groupName): bool
    {
        return $this->paymentMethodGroup === $groupName;
    }
}
