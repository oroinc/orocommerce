<?php

namespace OroB2B\Bundle\PricingBundle\SubtotalProcessor;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

class TotalProcessorProvider
{
    const NAME = 'orob2b_pricing.subtotal_total';
    const TYPE = 'total';
    const DEFAULT_CURRENCY = 'USD';
    const SUBTOTALS = 'subtotals';

    /** @var SubtotalProviderRegistry */
    protected $subtotalProviderRegistry;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RoundingServiceInterface */
    protected $rounding;

    /** @var  [] */
    protected $subtotals = [];

    /**
     * @param SubtotalProviderRegistry $subtotalProviderRegistry
     * @param TranslatorInterface $translator
     * @param RoundingServiceInterface $rounding
     */
    public function __construct(
        SubtotalProviderRegistry $subtotalProviderRegistry,
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding
    ) {
        $this->subtotalProviderRegistry = $subtotalProviderRegistry;
        $this->translator = $translator;
        $this->rounding = $rounding;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * Calculate and return total with subtotals converted to Array
     *
     * @param $entity
     *
     * @return array
     */
    public function getTotalWithSubtotalsAsArray($entity)
    {
        return [
            self::TYPE => $this->getTotal($entity)->toArray(),
            self::SUBTOTALS => $this->getSubtotals($entity)
                ->map(
                    function (Subtotal $subtotal) {
                        return $subtotal->toArray();
                    }
                )
                ->toArray(),
        ];
    }

    /**
     * Calculate and return total based on all subtotals
     *
     * @param $entity
     *
     * @return Subtotal
     */
    public function getTotal($entity)
    {
        $total = new Subtotal();

        $total->setType(self::TYPE);
        $translation = sprintf('orob2b.pricing.subtotals.%s.label', $total->getType());
        $total->setLabel($this->translator->trans($translation));

        $totalAmount = 0.0;
        foreach ($this->getSubtotals($entity) as $subtotal) {
            $rowTotal = $subtotal->getAmount();

            if ($this->getBaseCurrency($entity) !== $subtotal->getCurrency()) {
                $rowTotal *= $this->getExchangeRate($subtotal->getCurrency(), $this->getBaseCurrency($entity));
            }
            $totalAmount = $this->calculateTotal($subtotal->getOperation(), $rowTotal, $totalAmount);
        }
        $total->setAmount($this->rounding->round($totalAmount));
        $total->setCurrency($this->getBaseCurrency($entity));

        return $total;
    }

    /**
     * Collect all entity subtotals
     *
     * @param object $entity
     *
     * @return ArrayCollection|Subtotal[]
     */
    public function getSubtotals($entity)
    {
        $subtotalCollection = new ArrayCollection();
        if (!is_object($entity)) {
            throw new \InvalidArgumentException('Function parameter "entity" should be object.');
        }
        $hash = spl_object_hash($entity);

        if (!array_key_exists($hash, $this->subtotals)) {
            foreach ($this->subtotalProviderRegistry->getSupportedProviders($entity) as $provider) {
                $subtotals = $provider->getSubtotal($entity);
                $subtotals = is_object($subtotals) ? [$subtotals] : (array) $subtotals;
                foreach ($subtotals as $subtotal) {
                    $subtotalCollection->add($subtotal);
                }
            }
            $this->subtotals[$hash] = $subtotalCollection;
        }

        return $this->subtotals[$hash];
    }

    /**
     * @param $entity
     *
     * @return string
     */
    protected function getBaseCurrency($entity)
    {
        if (!$entity instanceof CurrencyAwareInterface) {
            return self::DEFAULT_CURRENCY;
        } else {
            return $entity->getCurrency();
        }
    }

    /**
     * Clear subtotals cache
     */
    public function clearCache()
    {
        $this->subtotals = [];
    }

    /**
     * @param string $fromCurrency
     * @param string $toCurrency
     *
     * @return float
     */
    protected function getExchangeRate($fromCurrency, $toCurrency)
    {
        return 1.0;
    }

    /**
     * @param int $operation
     * @param float $rowTotal
     * @param float $totalAmount
     *
     * @return mixed
     */
    protected function calculateTotal($operation, $rowTotal, $totalAmount)
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
}
