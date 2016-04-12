<?php

namespace OroB2B\Bundle\PaymentBundle\Layout\DataProvider;

use Oro\Component\Layout\DataProviderInterface;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;

class PaymentMethodsProvider implements DataProviderInterface
{
    const NAME = 'orob2b_payment_methods_provider';

    /**
     * @var array[]
     */
    protected $data;

    /**
     * @var PaymentMethodViewRegistry
     */
    protected $registry;

    /**
     * @param PaymentMethodViewRegistry $registry
     */
    public function __construct(PaymentMethodViewRegistry $registry)
    {
        $this->registry = $registry;
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
            $views = $this->registry->getPaymentMethodViews();
            foreach ($views as $name => $view) {
                $this->data[$name] = [
                    'label' => $view->getLabel(),
                    'block' => $view->getBlock(),
                    'options' => $view->getOptions(),
                ];
            }
        }

        return $this->data;
    }
}
