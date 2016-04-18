<?php

namespace OroB2B\Bundle\CheckoutBundle\Twig;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class LineItems extends \Twig_Extension
{
    const NAME = 'orob2b_checkout_order_line_items';

    /**
     * @var TotalProcessorProvider
     */
    protected $totalsProvider;

    /**
     * @param $totalsProvider
     */
    public function __construct($totalsProvider)
    {
        $this->totalsProvider = $totalsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [new \Twig_SimpleFunction('order_line_items', [$this, 'getOrderLineItems'])];
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getOrderLineItems(Order $order)
    {
        $lineItems = [];
        foreach ($order->getLineItems() as $lineItem) {
            $data['name'] = $lineItem->__toString();
            $data['quantity'] = $lineItem->getQuantity();
            $data['unit'] = $lineItem->getProductUnit();
            $data['price'] = $lineItem->getPrice();
            $data['subtotal'] = $lineItem->getSubtotal();
            $lineItems[] = $data;
        }
        $result['lineItems'] = $lineItems;
        $subtotals = [];
        foreach ($this->totalsProvider->getSubtotals($order) as $subtotal) {
            $subtotals[] = ['label' => $subtotal->getLabel(), 'totalPrice' => $subtotal->getTotalPrice()];
        }
        $result['subtotals'] = $subtotals;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
