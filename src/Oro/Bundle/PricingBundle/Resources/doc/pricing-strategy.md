Pricing Strategy
================
To combine prices for Customers, a few predefined Pricing Strategies exist:
- [Merge by priority](pricing_strategy_merge_by_priority.md)
- [Minimal prices](pricing_strategy_minimal_prices.md)

To create your own strategy, implement the `PriceCombiningStrategyInterface` interface and register the service with the '`oro_pricing.price_strategy`' tag.
Example:

    oro_pricing.pricing_strategy.merge_price_combining_strategy:
        class: Acme\Bundle\AcmeBundle\PricingStrategy\YourPricingStrategy
        parent: acme.pricing_strategy.your_pricing_strategy
        tags:
            - { name: acme.price_strategy, alias: your_strategy }
            
After this, your strategy will be available in System\Configuration\Commerce\Catalog\Pricing\Pricing Strategy.
