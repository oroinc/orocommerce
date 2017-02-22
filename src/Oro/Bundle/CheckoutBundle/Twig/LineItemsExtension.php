<?php

namespace Oro\Bundle\CheckoutBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class LineItemsExtension extends \Twig_Extension
{
    const NAME = 'oro_checkout_order_line_items';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return TotalProcessorProvider
     */
    protected function getTotalsProvider()
    {
        return $this->container->get('oro_pricing.subtotal_processor.total_processor_provider');
    }

    /**
     * @return LineItemSubtotalProvider
     */
    protected function getLineItemSubtotalProvider()
    {
        return $this->container->get('oro_pricing.subtotal_processor.provider.subtotal_line_item');
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
            $product = $lineItem->getProduct();
            $data['product_name'] = $product ? (string)$product : $lineItem->getFreeFormProduct();
            $data['product_sku'] = $lineItem->getProductSku();
            $data['quantity'] = $lineItem->getQuantity();
            $data['unit'] = $lineItem->getProductUnit();
            $data['price'] = $lineItem->getPrice();
            $data['subtotal'] = Price::create(
                $this->getLineItemSubtotalProvider()->getRowTotal($lineItem, $order->getCurrency()),
                $order->getCurrency()
            );
            $lineItems[] = $data;
        }
        $result['lineItems'] = $lineItems;
        $subtotals = [];
        foreach ($this->getTotalsProvider()->getSubtotals($order) as $subtotal) {
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
