<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\TaxBundle\Model\ResultElement;

class InvoiceTotalsProvider implements InvoiceTotalsProviderInterface
{
    /**
     * @var TotalProcessorProvider
     */
    protected $totalsProvider;

    /**
     * InvoiceTotalsProvider constructor.
     *
     * @param TotalProcessorProvider $totalProcessorProvider
     */
    public function __construct(TotalProcessorProvider $totalProcessorProvider)
    {
        $this->totalsProvider = $totalProcessorProvider;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    public function getTax(Order $order)
    {
        return $this->getSubtotalComponentByType('tax', $order);
    }

    /**
     * @param Order $order
     *
     * @return ResultElement
     */
    public function getTaxTotals(Order $order)
    {
        return $this->getAmountsByType('total', $order);
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    public function getTaxShipping(Order $order)
    {
        return $this->getAmountsByType('shipping', $order);
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    public function getDiscount(Order $order)
    {
        return $this->getSubtotalComponentByType('discount', $order);
    }

    /**
     * @param Order $order
     *
     * @return string|null
     */
    public function getTotalGrossAmount(Order $order)
    {
        $totals = $this->getTotalsByTypeArray('total', $order);
        if (!array_key_exists('amount', $totals)) {
            return null;
        }

        return $totals['amount'];
    }

    /**
     * @param string $type
     * @param Order  $order
     *
     * @return ResultElement
     */
    private function getAmountsByType($type, Order $order)
    {
        if (array_key_exists($type, $this->getTax($order)['data'])) {
            return $this->getTax($order)['data'][$type];
        }

        return ResultElement::create(0, 0);
    }

    /**
     * @param Order $order
     * @param string$type
     *
     * @return array
     */
    private function getSubtotalComponentByType($type, Order $order)
    {
        $subTotalArray = $this->getTotalsByTypeArray('subtotals', $order);
        foreach ($subTotalArray as $component) {
            if ($component['type'] === $type) {
                return $component;
            }
        }

        return ['amount' => 0];
    }

    /**
     * @param $totalsType
     * @param Order $order
     *
     * @return array
     */
    private function getTotalsByTypeArray($totalsType, Order $order)
    {
        $subtotals = $this->getTotalsArray($order);

        return array_key_exists($totalsType, $subtotals) ? $subtotals[$totalsType] : [];
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    private function getTotalsArray(Order $order)
    {
        return $this->totalsProvider->getTotalWithSubtotalsAsArray($order);
    }
}
