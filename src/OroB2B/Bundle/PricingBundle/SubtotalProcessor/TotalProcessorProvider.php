<?php

namespace OroB2B\Bundle\PricingBundle\SubtotalProcessor;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\CacheAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalCacheAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

class TotalProcessorProvider extends AbstractSubtotalProvider
{
    const NAME = 'orob2b_pricing.subtotal_total';
    const TYPE = 'total';
    const SUBTOTALS = 'subtotals';

    /** @var SubtotalProviderRegistry */
    protected $subtotalProviderRegistry;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RoundingServiceInterface */
    protected $rounding;

    /** @var  [] */
    protected $subtotals = [];

    /** @var bool */
    protected $recalculationEnabled = false;

    /**
     * @param SubtotalProviderRegistry $subtotalProviderRegistry
     * @param TranslatorInterface $translator
     * @param RoundingServiceInterface $rounding
     * @param UserCurrencyManager $currencyManager
     */
    public function __construct(
        SubtotalProviderRegistry $subtotalProviderRegistry,
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        UserCurrencyManager $currencyManager
    ) {
        parent::__construct($currencyManager);
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
        $baseCurrency = $this->getBaseCurrency($entity);
        $total->setCurrency($baseCurrency);

        $totalAmount = 0.0;
        foreach ($this->getSubtotals($entity) as $subtotal) {
            $rowTotal = $subtotal->getAmount();

            if ($baseCurrency !== $subtotal->getCurrency()) {
                $rowTotal *= $this->getExchangeRate($subtotal->getCurrency(), $baseCurrency);
            }
            $totalAmount = $this->calculateTotal($subtotal->getOperation(), $rowTotal, $totalAmount);
        }
        $total->setAmount($this->rounding->round($totalAmount));

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
                $subtotals = $this->getEntitySubtotal($provider, $entity);
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
     * @param SubtotalProviderInterface $provider
     * @param object $entity
     * @return Subtotal
     */
    protected function getEntitySubtotal(SubtotalProviderInterface $provider, $entity)
    {
        if ($this->recalculationEnabled) {
            return $provider->getSubtotal($entity);
        }

        if ($provider instanceof CacheAwareInterface) {
            return $provider->getCachedSubtotal($entity);
        }

        if ($provider instanceof SubtotalCacheAwareInterface) {
            if (!$entity instanceof SubtotalAwareInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        '"%s" expected, but "%s" given',
                        SubtotalAwareInterface::class,
                        is_object($entity) ? get_class($entity) : gettype($entity)
                    )
                );
            }

            return $provider->getCachedSubtotal($entity);
        }

        return $provider->getSubtotal($entity);
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

    /**
     * @return $this
     */
    public function enableRecalculation()
    {
        $this->recalculationEnabled = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableRecalculation()
    {
        $this->recalculationEnabled = false;

        return $this;
    }
}
