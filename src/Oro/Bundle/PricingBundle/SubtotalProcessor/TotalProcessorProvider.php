<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\CacheAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalCacheAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles logic for fetching totals for certain entity passed.
 */
class TotalProcessorProvider extends AbstractSubtotalProvider
{
    public const TYPE = 'total';
    public const SUBTOTALS = 'subtotals';

    private SubtotalProviderRegistry $subtotalProviderRegistry;
    private TranslatorInterface $translator;
    private RoundingServiceInterface $rounding;
    private bool $recalculationEnabled = false;

    public function __construct(
        SubtotalProviderRegistry $subtotalProviderRegistry,
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        SubtotalProviderConstructorArguments $arguments
    ) {
        parent::__construct($arguments);
        $this->subtotalProviderRegistry = $subtotalProviderRegistry;
        $this->translator = $translator;
        $this->rounding = $rounding;
    }

    public function getName(): string
    {
        return 'oro_pricing.subtotal_total';
    }

    /**
     * Calculates and returns total with subtotals converted to an array.
     */
    public function getTotalWithSubtotalsAsArray(object $entity): array
    {
        $subtotals = $this->getSubtotals($entity);

        return [
            self::TYPE => $this->getTotalForSubtotals($entity, $subtotals)->toArray(),
            self::SUBTOTALS => $subtotals
                ->map(function (Subtotal $subtotal) {
                    return $subtotal->toArray();
                })
                ->toArray()
        ];
    }

    public function getTotal(object $entity): Subtotal
    {
        return $this->createTotal($entity);
    }

    private function createTotal(object $entity, ArrayCollection $subtotals = null): Subtotal
    {
        $total = new Subtotal();

        $total->setType(self::TYPE);
        $translation = sprintf('oro.pricing.subtotals.%s.label', $total->getType());
        $total->setLabel($this->translator->trans($translation));
        $total->setCurrency($this->getBaseCurrency($entity));

        $totalAmount = 0.0;
        $subtotalsToProcess = null === $subtotals || $subtotals->isEmpty()
            ? $this->getSubtotals($entity)
            : $subtotals;
        foreach ($subtotalsToProcess as $subtotal) {
            $totalAmount = $this->calculateTotal($subtotal->getOperation(), $subtotal->getAmount(), $totalAmount);
            if ($subtotal->isRemovable()) {
                $subtotalsToProcess->removeElement($subtotal);
            }
        }
        $total->setAmount($this->rounding->round($totalAmount));

        return $total;
    }

    /**
     * Calculates and returns total based on all subtotals (which is already calculated).
     * This method is optimized alternative of "createTotal".
     */
    public function getTotalForSubtotals(object $entity, ArrayCollection $subtotals): Subtotal
    {
        return $this->createTotal($entity, $subtotals);
    }

    /**
     * Collects all entity subtotals.
     *
     * @psalm-return ArrayCollection<int, Subtotal>
     */
    public function getSubtotals(object $entity): ArrayCollection
    {
        $subtotals = [];
        foreach ($this->subtotalProviderRegistry->getSupportedProviders($entity) as $provider) {
            $entitySubtotals = $this->getEntitySubtotals($provider, $entity);
            foreach ($entitySubtotals as $subtotal) {
                $subtotals[] = $subtotal;
            }
        }

        usort($subtotals, function (Subtotal $leftSubtotal, Subtotal $rightSubtotal) {
            return $leftSubtotal->getSortOrder() - $rightSubtotal->getSortOrder();
        });

        return new ArrayCollection($subtotals);
    }

    private function getEntitySubtotals(SubtotalProviderInterface $provider, object $entity): array
    {
        if ($this->recalculationEnabled) {
            $result = $provider->getSubtotal($entity);
        } elseif ($provider instanceof CacheAwareInterface && $provider->supportsCachedSubtotal($entity)) {
            $result = $provider->getCachedSubtotal($entity);
        } elseif ($provider instanceof SubtotalCacheAwareInterface) {
            if (!$entity instanceof SubtotalAwareInterface) {
                throw new \InvalidArgumentException(sprintf(
                    '"%s" expected, but "%s" given',
                    SubtotalAwareInterface::class,
                    get_debug_type($entity)
                ));
            }
            $result = $provider->getCachedSubtotal($entity);
        } else {
            $result = $provider->getSubtotal($entity);
        }

        if (!\is_array($result)) {
            $result = [$result];
        }

        return $result;
    }

    private function calculateTotal(int $operation, float $rowTotal, float $totalAmount): float
    {
        if ($operation === Subtotal::OPERATION_ADD) {
            $totalAmount += $rowTotal;
        } elseif ($operation === Subtotal::OPERATION_SUBTRACTION) {
            $totalAmount -= $rowTotal;
        }
        if ($totalAmount < 0) {
            $totalAmount = 0.0;
        }

        return $totalAmount;
    }

    public function enableRecalculation(): self
    {
        $this->recalculationEnabled = true;

        return $this;
    }

    public function disableRecalculation(): self
    {
        $this->recalculationEnabled = false;

        return $this;
    }
}
