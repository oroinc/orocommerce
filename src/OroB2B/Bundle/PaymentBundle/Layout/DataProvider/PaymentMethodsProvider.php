<?php

namespace OroB2B\Bundle\PaymentBundle\Layout\DataProvider;

use Oro\Component\Layout\DataProviderInterface;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentContextProvider;

class PaymentMethodsProvider implements DataProviderInterface
{
    const NAME = 'orob2b_payment_methods_provider';

    /**
     * @var array[]
     */
    protected $data;

    /** @var PaymentMethodViewRegistry */
    protected $registry;

    /** @var PaymentContextProvider */
    protected $paymentContextProvider;

    /**
     * @param PaymentMethodViewRegistry $registry
     * @param PaymentContextProvider $paymentContextProvider
     */
    public function __construct(PaymentMethodViewRegistry $registry, PaymentContextProvider $paymentContextProvider)
    {
        $this->registry = $registry;
        $this->paymentContextProvider = $paymentContextProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return self::NAME;
    }

    /** {@inheritdoc} */
    public function getData(ContextInterface $context)
    {
        if (null === $this->data) {
            $entity = $this->getEntity($context);
            $paymentContext = $this->paymentContextProvider->processContext($context, $entity);

            $views = $this->registry->getPaymentMethodViews($paymentContext);
            foreach ($views as $name => $view) {
                $this->data[$name] = [
                    'label' => $view->getLabel(),
                    'block' => $view->getBlock(),
                    'options' => $view->getOptions($paymentContext),
                ];
            }
        }

        return $this->data;
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
