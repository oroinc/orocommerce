<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\CacheAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;

/**
 * The only registered tax subtotal provider. It resolves the tax result once and builds the tax,
 * shipping tax and line item tax subtotals from that single result, delegating to the three tax
 * subtotal providers, so the tax result is not requested once per provider.
 */
class TaxSubtotalProviderComposite implements SubtotalProviderInterface, CacheAwareInterface
{
    /** @var AbstractTaxSubtotalProvider[] */
    private array $providers;

    public function __construct(
        private readonly TaxProviderRegistry $taxProviderRegistry,
        private readonly TaxFactory $taxFactory,
        AbstractTaxSubtotalProvider ...$providers
    ) {
        $this->providers = $providers;
    }

    /**
     * @return Subtotal[]
     */
    #[\Override]
    public function getSubtotal($entity)
    {
        return $this->collectSubtotals($entity, fn () => $this->getProvider()->getTax($entity));
    }

    /**
     * @return Subtotal[]
     */
    #[\Override]
    public function getCachedSubtotal($entity)
    {
        return $this->collectSubtotals($entity, fn () => $this->getProvider()->loadTax($entity));
    }

    #[\Override]
    public function isSupported($entity)
    {
        return $this->taxFactory->supports($entity);
    }

    #[\Override]
    public function supportsCachedSubtotal($entity)
    {
        return $this->taxFactory->supports($entity);
    }

    /**
     * Resolves the tax result once and builds every tax subtotal from it.
     *
     * @return Subtotal[]
     */
    private function collectSubtotals(object $entity, callable $resolveTaxResult): array
    {
        try {
            $tax = $resolveTaxResult();
        } catch (TaxationDisabledException $e) {
            return array_map(
                static fn (AbstractTaxSubtotalProvider $provider) => $provider->createEmptySubtotal(),
                $this->providers
            );
        }

        return array_map(
            static fn (AbstractTaxSubtotalProvider $provider) => $provider->getSubtotalByResult($tax, $entity),
            $this->providers
        );
    }

    private function getProvider(): TaxProviderInterface
    {
        return $this->taxProviderRegistry->getEnabledProvider();
    }
}
