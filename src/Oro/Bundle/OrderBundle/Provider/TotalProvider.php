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
 * Handles logic for fetching totals based on order
 */
class TotalProvider
{
    /** @var DefaultCurrencyProviderInterface  */
    protected $defaultCurrencyProvider;

    /** @var TotalProcessorProvider  */
    protected $pricingTotal;

    /** @var RateConverterInterface  */
    protected $rateConverter;

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
     * Calculate and return total with subtotals
     * and with values in base currency converted to Array
     * Used by Orders
     *
     * @param Order $order
     * @param bool $isStatic
     * @return array
     */
    public function getTotalWithSubtotalsWithBaseCurrencyValues(Order $order, $isStatic = true)
    {
        $defaultCurrency = $this->defaultCurrencyProvider->getDefaultCurrency();
        $subtotals = $this->pricingTotal->getSubtotals($order);
        $total = $this->pricingTotal->getTotalForSubtotals($order, $subtotals);
        if ($total->getCurrency() !== $defaultCurrency) {
            $baseAmount = $isStatic ? $order->getBaseTotalValue() : null;
            $this->addSubtotalBaseCurrencyConversion($total, $defaultCurrency, $baseAmount);
        }

        foreach ($subtotals as $item) {
            if ($item->getType() == LineItemSubtotalProvider::TYPE
                && $item->getCurrency() !== $defaultCurrency
            ) {
                $baseAmount = $isStatic ? $order->getBaseSubtotalValue() : null;
                $this->addSubtotalBaseCurrencyConversion($item, $defaultCurrency, $baseAmount);
            }
        }

        return [
            TotalProcessorProvider::TYPE => $total->toArray(),
            TotalProcessorProvider::SUBTOTALS => $subtotals
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
     * @param null $baseAmount
     */
    protected function addSubtotalBaseCurrencyConversion(Subtotal $total, $defaultCurrency, $baseAmount = null)
    {
        if ($baseAmount) {
            $baseSubtotalValue = $baseAmount;
        } else {
            $baseSubtotal = MultiCurrency::create(
                $total->getAmount(),
                $total->getCurrency()
            );
            $baseSubtotalValue = $this->rateConverter->getBaseCurrencyAmount($baseSubtotal);
        }

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
}
