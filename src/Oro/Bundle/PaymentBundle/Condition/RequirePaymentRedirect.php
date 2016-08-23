<?php

namespace Oro\Bundle\PaymentBundle\Condition;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;

/**
 * Check that the payment method requires method verification after page refresh
 * Usage:
 * @require_payment_redirect:
 *      payment_method: 'payment_term'
 */
class RequirePaymentRedirect extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'require_payment_redirect';

    /**
     * @var PaymentMethodRegistry
     */
    private $paymentMethodRegistry;

    /**
     * @var string
     */
    private $paymentMethod;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param PaymentMethodRegistry $paymentMethodRegistry
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(PaymentMethodRegistry $paymentMethodRegistry, EventDispatcherInterface $eventDispatcher)
    {
        $this->paymentMethodRegistry = $paymentMethodRegistry;
        $this->eventDispatcher = $eventDispatcher;
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
    public function initialize(array $options)
    {
        if (empty($options['payment_method'])) {
            throw new \InvalidArgumentException('Parameter "payment_method" is required');
        }

        $this->paymentMethod = $options['payment_method'];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $paymentMethodName = $this->resolveValue($context, $this->paymentMethod);
        $paymentMethod = $this->paymentMethodRegistry->getPaymentMethod($paymentMethodName);

        $event = new RequirePaymentRedirectEvent($paymentMethod);
        $this->eventDispatcher->dispatch(RequirePaymentRedirectEvent::EVENT_NAME, $event);
        $this->eventDispatcher->dispatch(
            sprintf('%s.%s', RequirePaymentRedirectEvent::EVENT_NAME, $paymentMethodName),
            $event
        );

        return $event->isRedirectRequired();
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->paymentMethod]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->paymentMethod], $factoryAccessor);
    }
}
