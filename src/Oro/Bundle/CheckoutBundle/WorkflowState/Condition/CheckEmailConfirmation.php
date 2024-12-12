<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Condition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Check if the customer's email is confirmed.
 */
class CheckEmailConfirmation extends AbstractCondition implements ContextAccessorAwareInterface
{
    use FeatureCheckerHolderTrait;
    use ContextAccessorAwareTrait;

    public const NAME = 'is_email_confirmed';
    private const OPTION_KEY_CHECKOUT = 'checkout';

    private $checkout;

    #[\Override]
    public function initialize(array $options): self
    {
        $this->checkout = $this->getValueFromOption($options, self::OPTION_KEY_CHECKOUT);

        return $this;
    }

    #[\Override]
    protected function isConditionAllowed($context): bool
    {
        if ($this->isFeaturesEnabled()) {
            return true;
        }

        $checkout = $this->resolveValue($context, $this->checkout);
        if (!$checkout instanceof Checkout) {
            return false;
        }

        $registeredCustomerUser = $checkout->getRegisteredCustomerUser();

        // Ignore guests without an email address.
        if (!$registeredCustomerUser) {
            return true;
        }

        return $registeredCustomerUser->isConfirmed();
    }

    protected function getValueFromOption(array $options, string $key): mixed
    {
        if (!array_key_exists($key, $options)) {
            throw new InvalidArgumentException(sprintf('Missing "%s" option', $key));
        }

        return $options[$key];
    }

    #[\Override]
    protected function getMessage(): string
    {
        return 'oro.checkout.confirm_email_flash_message';
    }

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function toArray(): array
    {
        return $this->convertToArray([$this->checkout]);
    }

    #[\Override]
    public function compile($factoryAccessor): string
    {
        return $this->convertToPhpCode([$this->checkout], $factoryAccessor);
    }
}
