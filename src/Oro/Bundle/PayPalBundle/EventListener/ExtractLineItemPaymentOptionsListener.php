<?php

namespace Oro\Bundle\PayPalBundle\EventListener;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\LineItems;

/**
 * Modify payment line items to fit PayPal requirements.
 */
class ExtractLineItemPaymentOptionsListener
{
    const PAYPAL_PRICE_PRECISION_LIMIT = 2;

    /** @var NumberFormatter */
    protected $currencyFormatter;

    /** @var RoundingServiceInterface */
    protected $rounder;

    /**
     * @param NumberFormatter $currencyFormatter
     * @param RoundingServiceInterface $rounder
     */
    public function __construct(NumberFormatter $currencyFormatter, RoundingServiceInterface $rounder)
    {
        $this->currencyFormatter = $currencyFormatter;
        $this->rounder = $rounder;
    }

    /**
     * @param ExtractLineItemPaymentOptionsEvent $event
     */
    public function onExtractLineItemPaymentOptions(ExtractLineItemPaymentOptionsEvent $event)
    {
        $lineItemModels = $event->getModels();

        /** @var LineItemOptionModel $lineItemModel */
        foreach ($lineItemModels as $lineItemModel) {
            $name = $lineItemModel->getName();
            $cost = $lineItemModel->getCost();
            $qty = $lineItemModel->getQty();

            // PayPal doesn't support float quantities and prices with precision more than 2.
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
                ->setCost($this->roundForPayPal($cost));
        }
    }

    /**
     * @param float $number
     * @param int $precision
     * @return bool
     */
    protected function isPrecisionMoreThan($number, $precision)
    {
        return (bool) ($number - round($number, $precision));
    }

    /**
     * @param float $number
     * @return float|int
     */
    protected function roundForPayPal($number)
    {
        $precision = $this->rounder->getPrecision();

        if ($precision > self::PAYPAL_PRICE_PRECISION_LIMIT) {
            $precision = self::PAYPAL_PRICE_PRECISION_LIMIT;
        }

        return $this->rounder->round($number, $precision);
    }
}
