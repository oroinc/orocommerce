<?php

namespace Oro\Bundle\ApruveBundle\Layout\ContextConfigurator;

use Oro\Bundle\ApruveBundle\Method\Config\Provider\ApruveConfigProviderInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

class ApruveContextConfigurator implements ContextConfiguratorInterface
{
    /**
     * @var ApruveConfigProviderInterface
     */
    protected $apruveConfigProvider;

    /**
     * @param ApruveConfigProviderInterface $apruveConfigProvider
     */
    public function __construct(ApruveConfigProviderInterface $apruveConfigProvider)
    {
        $this->apruveConfigProvider = $apruveConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        if ($this->isSupported($context)) {
            $context->getResolver()
                ->setDefined(['is_apruve'])
                ->setAllowedTypes(['is_apruve' => ['boolean']]);

            $context->set('is_apruve', $this->isApruve($context));
        }
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    protected function isApruve(ContextInterface $context)
    {
        $data = $context->data();
        /** @var Checkout $checkout */
        $checkout = $data->get('checkout');

        return $this->apruveConfigProvider->hasPaymentConfig($checkout->getPaymentMethod());
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    protected function isSupported(ContextInterface $context)
    {
        return $context->has('workflowName')
            && $context->get('workflowName') === 'b2b_flow_checkout'
            && $context->get('workflowStepName') === 'order_review';
    }
}
