<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;

interface InvoiceTotalsProviderInterface
{
    /**
     * @param Order $order
     *
     * @return array
     */
    public function getTax(Order $order);

    /**
     * @param Order $order
     *
     * @return string|null
     */
    public function getTotalGrossAmount(Order $order);

    /**
     * @param Order $order
     *
     * @return array
     */
    public function getDiscount(Order $order);

    /**
     * @param Order $order
     *
     * @return array
     */
    public function getTaxTotals(Order $order);

    /**
     * @param Order $order
     *
     * @return array
     */
    public function getTaxShipping(Order $order);
}
