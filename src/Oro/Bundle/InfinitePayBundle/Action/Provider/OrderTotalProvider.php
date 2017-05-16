<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Model\ResultElement;

class OrderTotalProvider implements OrderTotalProviderInterface
{
    /**
     * @var InvoiceTotalsProviderInterface
     */
    protected $invoiceTotalsProvider;

    /**
     * @param InvoiceTotalsProviderInterface $invoiceTotalsProvider
     */
    public function __construct(InvoiceTotalsProviderInterface $invoiceTotalsProvider)
    {
        $this->invoiceTotalsProvider = $invoiceTotalsProvider;
    }

    /**
     * @param Order $order
     *
     * @return OrderTotal
     */
    public function getOrderTotal(Order $order)
    {
        $discount = $this->invoiceTotalsProvider->getDiscount($order);
        $totalGrossAmount = $this->invoiceTotalsProvider->getTotalGrossAmount($order);

        /** @var ResultElement $taxTotals */
        $taxTotals = $this->invoiceTotalsProvider->getTaxTotals($order); //incl tax is w/o shipping

        /** @var ResultElement $taxShipping */
        $taxShipping = $this->invoiceTotalsProvider->getTaxShipping($order);

        $shippingGross = $this->convertToCentInt($taxShipping->getIncludingTax());
        $rebateGross = $this->convertToCentInt($discount[self::FIELD_AMOUNT]);
        $totalGross = $this->convertToCentInt($totalGrossAmount);

        $shippingNet = $this->convertToCentInt($taxShipping->getExcludingTax());
        $rebateNet = $this->convertToCentInt($discount[self::FIELD_AMOUNT]);
        $totalNet = $this->convertToCentInt($taxTotals->getExcludingTax());

        $orderTotal = new OrderTotal();
        $orderTotal
            ->setTrsCurrency($order->getCurrency())
            ->setTrsAmtGross($totalGross)
            ->setTrsAmtNet($totalNet)
            ->setPayType(self::PAY_TYPE_INVOICE)
            ->setRabateGross($rebateGross)
            ->setRabateNet($rebateNet)
            ->setShippingPriceGross($shippingGross)
            ->setShippingPriceNet($shippingNet)
            ->setTermsAccepted(self::PAY_TYPE_INVOICE)
            ->setTrsDt($this->getInitiationDatetime())
            ->setTotalGrossCalcMethod(static::TOTAL_CALC_B2B_TAX_PER_ITEM);

        return $orderTotal;
    }

    /**
     * @param float $input
     *
     * @return int
     */
    private function convertToCentInt($input)
    {
        return round($input * 100, 0, PHP_ROUND_HALF_UP);
//        return $input * 100;
    }

    /**
     * @return string
     */
    private function getInitiationDatetime()
    {
        return (new \DateTime())->format('Ymd His');
    }
}
