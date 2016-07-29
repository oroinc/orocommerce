<?php

namespace OroB2B\Bundle\PaymentBundle\Layout\DataProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentContextProvider;

class PaymentMethodsProvider extends AbstractServerRenderDataProvider
{
    const NAME = 'orob2b_payment_methods_provider';

    /**
     * @var array[]
     */
    protected $paymentMethodViews;

    /** @var PaymentMethodViewRegistry */
    protected $paymentMethodViewRegistry;

    /** @var PaymentContextProvider */
    protected $paymentContextProvider;

    /** @var PaymentMethodRegistry */
    protected $paymentMethodRegistry;

    /**
     * @param PaymentMethodViewRegistry $paymentMethodViewRegistry
     * @param PaymentContextProvider $paymentContextProvider
     * @param PaymentMethodRegistry $paymentMethodRegistry
     */
    public function __construct(
        PaymentMethodViewRegistry $paymentMethodViewRegistry,
        PaymentContextProvider $paymentContextProvider,
        PaymentMethodRegistry $paymentMethodRegistry
    ) {
        $this->paymentMethodViewRegistry = $paymentMethodViewRegistry;
        $this->paymentContextProvider = $paymentContextProvider;
        $this->paymentMethodRegistry = $paymentMethodRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return self::NAME;
    }

    /**
     * @param ContextInterface $context
     * @return array[]
     */
    public function getViews($context = null)
    {
        if (null === $this->paymentMethodViews) {
            $entity = $this->getEntity($context);
            $paymentContext = $this->paymentContextProvider->processContext(['entity'=> $entity], $entity);

            $views = $this->paymentMethodViewRegistry->getPaymentMethodViews($paymentContext);
            foreach ($views as $view) {
                $this->paymentMethodViews[$view->getPaymentMethodType()] = [
                    'label' => $view->getLabel(),
                    'block' => $view->getBlock(),
                    'options' => $view->getOptions($paymentContext),
                ];
            }
        }

        return $this->paymentMethodViews;
    }

    /**
     * @param $paymentMethod
     * @return bool
     */
    public function isPaymentMethodEnabled($paymentMethod)
    {
        try {
            return $this->paymentMethodRegistry->getPaymentMethod($paymentMethod)->isEnabled();
        } catch (\InvalidArgumentException $e) {
        }

        return false;
    }

    /**
     * @param $paymentMethodName
     * @param $entity
     * @return bool
     */
    public function isPaymentMethodApplicable($paymentMethodName, $entity)
    {
        try {
            $paymentMethod = $this->paymentMethodRegistry->getPaymentMethod($paymentMethodName);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        if (!$paymentMethod->isEnabled()) {
            return false;
        }
        $paymentContext = $this->paymentContextProvider->processContext(['entity'=> $entity], $entity);

        return $paymentMethod->isApplicable($paymentContext);
    }

    /**
     * @param $entity
     * @return bool
     */
    public function hasApplicablePaymentMethods($entity)
    {
        $paymentContext = $this->paymentContextProvider->processContext(['entity'=> $entity], $entity);

        $paymentMethods = $this->paymentMethodRegistry->getPaymentMethods();
        foreach ($paymentMethods as $paymentMethod) {
            if (!$paymentMethod->isEnabled()) {
                continue;
            }

            if (!$paymentMethod->isApplicable($paymentContext)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param ContextInterface $context
     * @return object|null
     */
    protected function getEntity(ContextInterface $context)
    {
        $entity = null;
        $contextData = $context->data();
        if ($contextData->has('entity')) {
            $entity = $contextData->get('entity');
        }
        if (!$entity && $contextData->has('checkout')) {
            $entity = $contextData->get('checkout');
        }
        return $entity;
    }
}
