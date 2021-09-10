<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

/**
 * The registry of payment method view providers.
 */
class CompositePaymentMethodViewProvider implements PaymentMethodViewProviderInterface
{
    /** @var iterable|PaymentMethodViewProviderInterface[] */
    private $providers;

    /**
     * @param iterable|PaymentMethodViewProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethodViews(array $identifiers)
    {
        $items = [];
        foreach ($this->providers as $provider) {
            $items[] = $provider->getPaymentMethodViews($identifiers);
        }
        if ($items) {
            $items = array_merge(...$items);
        }

        return $items;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethodView($identifier)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasPaymentMethodView($identifier)) {
                return $provider->getPaymentMethodView($identifier);
            }
        }

        throw new \InvalidArgumentException('There is no payment method view for "'.$identifier.'"');
    }

    /**
     * {@inheritDoc}
     */
    public function hasPaymentMethodView($identifier)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasPaymentMethodView($identifier)) {
                return true;
            }
        }

        return false;
    }
}
