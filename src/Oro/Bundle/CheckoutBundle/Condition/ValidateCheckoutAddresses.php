<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
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

    const NAME = 'validate_checkout_addresses';

    /**
     * @var mixed
     */
    private $checkout;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (array_key_exists('checkout', $options)) {
            $this->checkout = $options['checkout'];
        } elseif (array_key_exists(0, $options)) {
            $this->checkout = $options[0];
        }

        if (!$this->checkout) {
            throw new InvalidArgumentException('Missing "checkout" option');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function doEvaluate($context)
    {
        return $this->isConditionAllowed($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        /** @var Checkout $checkout */
        $checkout = $this->resolveValue($context, $this->checkout, false);

        if (!$checkout instanceof Checkout) {
            return false;
        }

        $billingAddress = $checkout->getBillingAddress();

        $result = true;
        if (!$billingAddress || count($this->validator->validate($billingAddress))) {
            $this->setMessage('oro.checkout.workflow.condition.invalid_billing_address.message');
            $this->addError($context);
            $result = false;
        }

        if ($checkout->isShipToBillingAddress()) {
            return $result;
        }

        $shippingAddress = $checkout->getShippingAddress();

        if (!$shippingAddress || count($this->validator->validate($shippingAddress))) {
            $this->setMessage('oro.checkout.workflow.condition.invalid_shipping_address.message');
            $this->addError($context);
            $result = false;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->checkout]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->checkout], $factoryAccessor);
    }
}
