<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates checkout billing and shipping addresses
 */
class ValidateCheckoutAddresses extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private mixed $checkout = null;

    public function __construct(
        private readonly ValidatorInterface $validator
    ) {
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (\array_key_exists('checkout', $options)) {
            $this->checkout = $options['checkout'];
        } elseif (\array_key_exists(0, $options)) {
            $this->checkout = $options[0];
        }
        if (!$this->checkout) {
            throw new InvalidArgumentException('Missing "checkout" option');
        }

        return $this;
    }

    #[\Override]
    public function getName()
    {
        return 'validate_checkout_addresses';
    }

    #[\Override]
    protected function doEvaluate($context)
    {
        return $this->isConditionAllowed($context);
    }

    #[\Override]
    protected function isConditionAllowed($context)
    {
        $checkout = $this->resolveValue($context, $this->checkout, false);

        if (!$checkout instanceof Checkout) {
            return false;
        }

        $result = $this->isAddressValid(
            $checkout->getBillingAddress(),
            'oro.checkout.workflow.condition.invalid_billing_address.message',
            $context
        );
        if ($result && !$checkout->isShipToBillingAddress()) {
            $result = $this->isAddressValid(
                $checkout->getShippingAddress(),
                'oro.checkout.workflow.condition.invalid_shipping_address.message',
                $context
            );
        }

        return $result;
    }

    private function isAddressValid(?OrderAddress $address, string $message, mixed $context): bool
    {
        if (!$address || \count($this->validator->validate($address))) {
            $this->setMessage($message);
            $this->addError($context);

            return false;
        }

        return true;
    }

    #[\Override]
    public function toArray()
    {
        return $this->convertToArray([$this->checkout]);
    }

    #[\Override]
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->checkout], $factoryAccessor);
    }
}
