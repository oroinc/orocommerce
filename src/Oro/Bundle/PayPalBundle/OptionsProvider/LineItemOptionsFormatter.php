<?php

namespace Oro\Bundle\PayPalBundle\OptionsProvider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\LineItems;

/**
 * PayPal doesn't support float quantities for line items.
 * This class handles such case and modify line item's quantity to 1, update price of this item accordingly.
 * Also it updates line item's name to mention this changes.
 * E.g.:
 *   before: qty=0.5, price=5, name="Some item name"
 *   after: qty=1, price=2.5, name="Some item name - $5x0.5 case"
 */
class LineItemOptionsFormatter
{
    private const PRICE_PRECISION_LIMIT = 2;

    /**
     * @var NumberFormatter
     */
    private $currencyFormatter;

    /**
     * @var RoundingServiceInterface
     */
    private $rounder;

    public function __construct(NumberFormatter $currencyFormatter, RoundingServiceInterface $rounder)
    {
        $this->currencyFormatter = $currencyFormatter;
        $this->rounder = $rounder;
    }

    /**
     * @param LineItemOptionModel[] $lineItemOptions
     * @return LineItemOptionModel[]
     */
    public function formatLineItemOptions(array $lineItemOptions): array
    {
        foreach ($lineItemOptions as $lineItemModel) {
            $name = $lineItemModel->getName();
            $cost = $lineItemModel->getCost();
            $qty = $lineItemModel->getQty();

            // PayPal doesn't support float quantities and prices with precision more than 2
            // Multiply qty by cost and add information about actual qty and price to line item name
            if ($this->isPrecisionMoreThan($qty, 0) || $this->isPrecisionMoreThan($cost, 2)) {
                $additionalNameInfo = sprintf(
                    ' - %sx%s %s',
                    $this->currencyFormatter->formatCurrency($cost, $lineItemModel->getCurrency()),
                    $qty,
                    $lineItemModel->getUnit()
                );

                $name = sprintf(
                    '%s%s',
                    // we can't use multibyte string functions here
                    // because PayPal doesn't use multibyte when calculating string length
                    substr($name, 0, LineItems::PAYPAL_NAME_LIMIT - strlen($additionalNameInfo)),
                    $additionalNameInfo
                );

                // Update cost and qty to have 1 item with cost = price * qty
                $cost *= $qty;
                $qty = 1;
            }

            $lineItemModel
                ->setName($name)
                ->setQty($qty)
                ->setCost($this->roundCost($cost));
        }

        return $lineItemOptions;
    }

    private function isPrecisionMoreThan(float $number, int $precision): bool
    {
        return (bool) ($number - round($number, $precision));
    }

    /**
     * @param float $number
     * @return float|int
     */
    private function roundCost(float $number)
    {
        $precision = $this->rounder->getPrecision();
        $resultPrecision = $precision > self::PRICE_PRECISION_LIMIT ? self::PRICE_PRECISION_LIMIT : $precision;

        return $this->rounder->round($number, $resultPrecision);
    }
}
