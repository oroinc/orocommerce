<?php

namespace Oro\Bundle\PaymentBundle\Condition;

use Oro\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @var PaymentMethodProviderInterface
     */
    private $paymentMethodProvider;

    /**
     * @var string
     */
    private $paymentMethod;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        PaymentMethodProviderInterface $paymentMethodProvider,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->paymentMethodProvider = $paymentMethodProvider;
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
        if (!$this->paymentMethodProvider->hasPaymentMethod($paymentMethodIdentifier)) {
            return false;
        }

        $paymentMethod = $this->paymentMethodProvider->getPaymentMethod($paymentMethodIdentifier);
        $event = new RequirePaymentRedirectEvent($paymentMethod);
        $this->eventDispatcher->dispatch($event, RequirePaymentRedirectEvent::EVENT_NAME);

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
