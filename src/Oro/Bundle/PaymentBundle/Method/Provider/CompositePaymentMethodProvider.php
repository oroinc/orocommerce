<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

/**
 * The registry of payment method providers.
 */
class CompositePaymentMethodProvider implements PaymentMethodProviderInterface
{
    /** @var iterable|PaymentMethodProviderInterface[] */
    private $providers;

    /**
     * @param iterable|PaymentMethodProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethods()
    {
        $items = [];
        foreach ($this->providers as $provider) {
            $items[] = $provider->getPaymentMethods();
        }
        if ($items) {
            $items = array_merge(...$items);
        }

        return $items;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethod($identifier)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasPaymentMethod($identifier)) {
                return $provider->getPaymentMethod($identifier);
            }
        }

        throw new \InvalidArgumentException('There is no payment method for "' . $identifier . '" identifier');
    }

    /**
     * {@inheritDoc}
     */
    public function hasPaymentMethod($identifier)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasPaymentMethod($identifier)) {
                return true;
            }
        }

        return false;
    }
}
