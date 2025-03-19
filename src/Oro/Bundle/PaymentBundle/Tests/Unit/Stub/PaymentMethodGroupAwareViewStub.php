<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Stub;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodGroupAwareInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

class PaymentMethodGroupAwareViewStub implements
    PaymentMethodViewInterface,
    PaymentMethodGroupAwareInterface
{
    public function __construct(private string $paymentMethodIdentifier, private string $paymentMethodGroup)
    {
    }

    #[\Override]
    public function getOptions(PaymentContextInterface $context): array
    {
        return ['sample_key' => 'sample_value'];
    }

    #[\Override]
    public function getBlock(): string
    {
        return $this->paymentMethodIdentifier . '_widget';
    }

    #[\Override]
    public function getLabel(): string
    {
        return $this->paymentMethodIdentifier . ' (label)';
    }

    #[\Override]
    public function getAdminLabel(): string
    {
        return $this->paymentMethodIdentifier . ' (admin label)';
    }

    #[\Override]
    public function getShortLabel(): string
    {
        return $this->paymentMethodIdentifier . ' (short label)';
    }

    #[\Override]
    public function getPaymentMethodIdentifier(): string
    {
        return $this->paymentMethodIdentifier;
    }

    #[\Override]
    public function isApplicableForGroup(string $groupName): bool
    {
        return $this->paymentMethodGroup === $groupName;
    }
}
