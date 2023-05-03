<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Provider\DefaultCurrencyProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

/**
 * Handles logic for fetching totals for a specific order.
 */
class TotalProvider
{
    private DefaultCurrencyProviderInterface $defaultCurrencyProvider;
    private TotalProcessorProvider $pricingTotal;
    private RateConverterInterface $rateConverter;

    public function __construct(
        TotalProcessorProvider $totalProvider,
        DefaultCurrencyProviderInterface $defaultCurrencyProvider,
        RateConverterInterface $rateConverter
    ) {
        $this->pricingTotal = $totalProvider;
        $this->defaultCurrencyProvider = $defaultCurrencyProvider;
        $this->rateConverter = $rateConverter;
    }

    /**
     * Calculates and returns a total with subtotals
     * and with values in base currency converted to an array.
     */
    public function getTotalWithSubtotalsWithBaseCurrencyValues(Order $order, bool $isStatic = true): array
    {
        $defaultCurrency = $this->defaultCurrencyProvider->getDefaultCurrency();
        $subtotals = $this->pricingTotal->getSubtotals($order);
        $total = $this->pricingTotal->getTotalForSubtotals($order, $subtotals);
        if ($total->getCurrency() !== $defaultCurrency) {
            $baseAmount = $isStatic ? $order->getBaseTotalValue() : null;
            $this->addSubtotalBaseCurrencyConversion($total, $defaultCurrency, $baseAmount);
        }

        foreach ($subtotals as $item) {
            if ($item->getType() === LineItemSubtotalProvider::TYPE
                && $item->getCurrency() !== $defaultCurrency
            ) {
                $baseAmount = $isStatic ? $order->getBaseSubtotalValue() : null;
                $this->addSubtotalBaseCurrencyConversion($item, $defaultCurrency, $baseAmount);
            }
        }

        return [
            TotalProcessorProvider::TYPE => $total->toArray(),
            TotalProcessorProvider::SUBTOTALS => $subtotals
                ->map(function (Subtotal $subtotal) {
                    return $subtotal->toArray();
                })
                ->toArray()
        ];
    }

    private function addSubtotalBaseCurrencyConversion(
        Subtotal $total,
        string $defaultCurrency,
        ?float $baseAmount = null
    ): void {
        if ($baseAmount) {
            $baseSubtotalValue = $baseAmount;
        } else {
            $baseSubtotalValue = $this->rateConverter->getBaseCurrencyAmount(
                MultiCurrency::create($total->getAmount(), $total->getCurrency())
            );
        }

        $data = $total->getData() ?? [];
        $totalData = array_merge($data, [
            'baseAmount' => $baseSubtotalValue,
            'baseCurrency' => $defaultCurrency
        ]);
        $total->setData($totalData);
    }
}
