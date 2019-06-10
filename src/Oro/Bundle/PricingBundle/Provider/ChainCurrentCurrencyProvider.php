<?php

namespace Oro\Bundle\PricingBundle\Provider;

/**
 * Delegates the getting of a current currency to child providers.
 */
class ChainCurrentCurrencyProvider implements CurrentCurrencyProviderInterface
{
    /** @var iterable|CurrentCurrencyProviderInterface[] */
    private $providers;

    /** @var string|null */
    private $currentCurrency = false;

    /**
     * @param iterable|CurrentCurrencyProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentCurrency(): ?string
    {
        if (false === $this->currentCurrency) {
            $this->currentCurrency = null;
            foreach ($this->providers as $provider) {
                $currency = $provider->getCurrentCurrency();
                if ($currency) {
                    $this->currentCurrency = $currency;
                    break;
                }
            }
        }

        return $this->currentCurrency;
    }
}
