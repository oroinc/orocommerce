<?php

namespace Oro\Bundle\CheckoutBundle\Event;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Fires within checkout search.
 */
final class CheckoutFindEvent extends Event
{
    public const string NAME = 'oro_checkout.find';

    public function __construct(
        private array $sourceCriteria,
        private ?UserInterface $customerUser,
        private ?string $currency,
        private ?string $workflowName,
        private ?Checkout $checkout
    ) {
    }

    public function getCheckout(): ?Checkout
    {
        return $this->checkout;
    }

    public function setCheckout(?Checkout $checkout): void
    {
        $this->checkout = $checkout;
    }

    public function getSourceCriteria(): array
    {
        return $this->sourceCriteria;
    }

    public function getCustomerUser(): ?UserInterface
    {
        return $this->customerUser;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getWorkflowName(): ?string
    {
        return $this->workflowName;
    }
}
