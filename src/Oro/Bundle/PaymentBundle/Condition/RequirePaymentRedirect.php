<?php

namespace Oro\Bundle\PaymentBundle\Condition;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProvidersRegistry;

/**
 * Check that the payment method requires method verification after page refresh
 * Usage:
 * @require_payment_redirect:
 *      payment_method: 'payment_method_name'
 */
class RequirePaymentRedirect extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'require_payment_redirect';

    /**
     * @var PaymentMethodProvidersRegistry
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
     * @param PaymentMethodProvidersRegistry $paymentMethodRegistry
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        PaymentMethodProvidersRegistry $paymentMethodRegistry,
        EventDispatcherInterface $eventDispatcher
    ) {
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
        $paymentMethodIdentifier = $this->resolveValue($context, $this->paymentMethod);
        foreach ($this->paymentMethodRegistry->getPaymentMethodProviders() as $provider) {
            if ($provider->hasPaymentMethod($paymentMethodIdentifier)) {
                $paymentMethod = $provider->getPaymentMethod($paymentMethodIdentifier);
                $event = new RequirePaymentRedirectEvent($paymentMethod);
                $this->eventDispatcher->dispatch(RequirePaymentRedirectEvent::EVENT_NAME, $event);
                $this->eventDispatcher->dispatch(
                    sprintf('%s.%s', RequirePaymentRedirectEvent::EVENT_NAME, $paymentMethodIdentifier),
                    $event
                );

                return $event->isRedirectRequired();
            }
        }

        return false;
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
