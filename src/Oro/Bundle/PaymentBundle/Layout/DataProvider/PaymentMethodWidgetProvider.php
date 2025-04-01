<?php

namespace Oro\Bundle\PaymentBundle\Layout\DataProvider;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodAwareInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;

/**
 * Provides a widget name for a given payment method based on the provided entity and prefix.
 */
class PaymentMethodWidgetProvider
{
    const NAME = 'oro_payment_method_widget_provider';

    /**
     * @var PaymentMethodViewProviderInterface
     */
    protected $paymentMethodViewProvider;

    public function __construct(PaymentMethodViewProviderInterface $paymentMethodViewProvider)
    {
        $this->paymentMethodViewProvider = $paymentMethodViewProvider;
    }

    /**
     * @param object $entity
     * @param string $prefix
     *
     * @return string
     */
    public function getPaymentMethodWidgetName($entity, $prefix)
    {
        if (!$entity instanceof PaymentMethodAwareInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Object "%s" must implement interface "%s"',
                is_object($entity) ? get_class($entity) : gettype($entity),
                PaymentMethodAwareInterface::class
            ));
        }
        try {
            $paymentMethodView = $this->paymentMethodViewProvider->getPaymentMethodView($entity->getPaymentMethod());
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        return sprintf('_%s%s', $prefix, $paymentMethodView->getBlock());
    }
}
