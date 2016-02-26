<?php

namespace OroB2B\Bundle\OrderBundle\Provider;

use OroB2B\Bundle\OrderBundle\Entity\Order;

class TotalProvider
{
    /**
     * @param Order $order
     *
     * @return array
     */
    public function getTotal(Order $order)
    {
        return [
            'subtotals' => [
                'tax' => [
                    'label' => 'Tax',
                    'value' => '100'
                ],
                'sub total' => [
                    'label' => 'Sub total',
                    'value' => '300'
                ]
            ],
            'total' => [
                'label' => 'Total',
                'value' => $order->getTotal()
            ]
        ];
    }
}
