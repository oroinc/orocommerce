<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Provider\DefaultCurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\CacheAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalCacheAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;

class TotalProcessorProvider extends AbstractSubtotalProvider
{
    const NAME = 'oro_pricing.subtotal_total';
    const TYPE = 'total';
    const SUBTOTALS = 'subtotals';

    /** @var SubtotalProviderRegistry */
    protected $subtotalProviderRegistry;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RoundingServiceInterface */
    protected $rounding;

    /** @var bool */
    protected $recalculationEnabled = false;

    /** @var RateConverterInterface  */
    protected $rateConverter;

    /** @var DefaultCurrencyProviderInterface  */
    protected $defaultCurrencyProvider;

    /**
     * @param SubtotalProviderRegistry $subtotalProviderRegistry
     * @param TranslatorInterface $translator
     * @param RoundingServiceInterface $rounding
     * @param UserCurrencyManager $currencyManager
     * @param RateConverterInterface $rateConverter
     * @param DefaultCurrencyProviderInterface $defaultCurrencyProvider
     */
    public function __construct(
        SubtotalProviderRegistry $subtotalProviderRegistry,
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        UserCurrencyManager $currencyManager,
        RateConverterInterface $rateConverter,
        DefaultCurrencyProviderInterface $defaultCurrencyProvider
    ) {
        parent::__construct($currencyManager);
        $this->subtotalProviderRegistry = $subtotalProviderRegistry;
        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->rateConverter = $rateConverter;
        $this->defaultCurrencyProvider = $defaultCurrencyProvider;
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
     * Calculate and return total with subtotals
     * and with values in base currency converted to Array
     * Used by Orders
     *
     * @param $entity
     *
     * @return array
     */
    public function getTotalWithSubtotalsWithBaseCurrencyValues($entity)
    {
        $defaultCurrency = $this->defaultCurrencyProvider->getDefaultCurrency();
        $total = $this->getTotal($entity);
        if ($total->getCurrency() !== $defaultCurrency) {
            $this->addSubtotalBaseCurrencyConversion($total, $defaultCurrency);
        }
        $subtotals = $this->getSubtotals($entity);
        foreach ($subtotals as $item) {
            if ($item->getType() == LineItemSubtotalProvider::TYPE
                && $item->getCurrency() !== $defaultCurrency
            ) {
                $this->addSubtotalBaseCurrencyConversion($item, $defaultCurrency);
            }
        }

        return [
            self::TYPE => $total->toArray(),
            self::SUBTOTALS => $subtotals
                ->map(
                    function (Subtotal $subtotal) {
                        return $subtotal->toArray();
                    }
                )
                ->toArray(),
        ];
    }

    /**
     * Set value in base currency to data
     * @param Subtotal $total
     * @param $defaultCurrency
     */
    protected function addSubtotalBaseCurrencyConversion(Subtotal $total, $defaultCurrency)
    {
        $baseSubtotal = MultiCurrency::create(
            $total->getAmount(),
            $total->getCurrency()
        );
        $baseSubtotalValue = $this->rateConverter->getBaseCurrencyAmount($baseSubtotal);
        $data = $total->getData() ? $total->getData() : [];
        $totalData = array_merge(
            $data,
            [
                'baseAmount' => $baseSubtotalValue,
                'baseCurrency' => $defaultCurrency
            ]
        );
        $total->setData($totalData);
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
        $translation = sprintf('oro.pricing.subtotals.%s.label', $total->getType());
        $total->setLabel($this->translator->trans($translation));
        $total->setCurrency($this->getBaseCurrency($entity));

        $totalAmount = 0.0;
        foreach ($this->getSubtotals($entity) as $subtotal) {
            $rowTotal = $subtotal->getAmount();

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

        foreach ($this->subtotalProviderRegistry->getSupportedProviders($entity) as $provider) {
            $subtotals = $this->getEntitySubtotal($provider, $entity);
            $subtotals = is_object($subtotals) ? [$subtotals] : (array) $subtotals;
            foreach ($subtotals as $subtotal) {
                $subtotalCollection->add($subtotal);
            }
        }

        return $subtotalCollection;
    }

    /**
     * @param SubtotalProviderInterface $provider
     * @param object $entity
     * @return Subtotal|Subtotal[]
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
     * @param int $operation
     * @param float $rowTotal
     * @param float $totalAmount
     *
     * @return float
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
