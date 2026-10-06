<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\CacheAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Model\Result;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract class of taxes subtotal provider.
 */
abstract class AbstractTaxSubtotalProvider implements SubtotalProviderInterface, CacheAwareInterface
{
    public const TYPE = 'tax';

    public function __construct(
        protected TranslatorInterface $translator,
        protected TaxProviderRegistry $taxProviderRegistry,
        protected TaxFactory $taxFactory,
        protected TaxationSettingsProvider $taxationSettingsProvider
    ) {
    }

    #[\Override]
    public function getSubtotal($entity)
    {
        try {
            $tax = $this->getProvider()->getTax($entity);
        } catch (TaxationDisabledException $e) {
            return $this->createSubtotal();
        }

        return $this->getSubtotalByResult($tax, $entity);
    }

    #[\Override]
    public function getCachedSubtotal($entity)
    {
        try {
            $tax = $this->getProvider()->loadTax($entity);
        } catch (TaxationDisabledException $e) {
            return $this->createSubtotal();
        }

        return $this->getSubtotalByResult($tax, $entity);
    }

    /**
     * Builds the subtotal from an already resolved tax result, without requesting it again.
     * Allows an orchestrating provider to share a single tax result between several subtotals.
     */
    public function getSubtotalByResult(Result $tax, object $entity): Subtotal
    {
        $subtotal = $this->createSubtotal();
        $this->fillSubtotal($subtotal, $tax, $entity);

        return $subtotal;
    }

    /**
     * Returns an empty subtotal, e.g. when taxation is disabled.
     */
    public function createEmptySubtotal(): Subtotal
    {
        return $this->createSubtotal();
    }

    abstract protected function createSubtotal(): Subtotal;

    abstract protected function fillSubtotal(Subtotal $subtotal, Result $tax, ?object $entity = null): Subtotal;

    #[\Override]
    public function isSupported($entity): bool
    {
        return $this->taxFactory->supports($entity);
    }

    #[\Override]
    public function supportsCachedSubtotal($entity): bool
    {
        return $this->taxFactory->supports($entity);
    }

    protected function getProvider(): TaxProviderInterface
    {
        return $this->taxProviderRegistry->getEnabledProvider();
    }
}
