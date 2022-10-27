<?php

namespace Oro\Bundle\CheckoutBundle\Twig;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to retrieve order data for checkout process,
 * including all products with amounts and subtotals:
 *   - order_line_items
 */
class LineItemsExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return TotalProcessorProvider
     */
    private function getTotalsProvider()
    {
        return $this->container->get(TotalProcessorProvider::class);
    }

    /**
     * @return LineItemSubtotalProvider
     */
    private function getLineItemSubtotalProvider()
    {
        return $this->container->get(LineItemSubtotalProvider::class);
    }

    /**
     * @return LocalizationHelper
     */
    private function getLocalizationHelper()
    {
        return $this->container->get(LocalizationHelper::class);
    }

    /**
     * @return EntityNameResolver
     */
    private function getEntityNameResolver()
    {
        return $this->container->get(EntityNameResolver::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [new TwigFunction('order_line_items', [$this, 'getOrderLineItems'])];
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
            $productName = $this->getEntityNameResolver()->getName(
                $product,
                EntityNameProviderInterface::FULL,
                $this->getLocalizationHelper()->getCurrentLocalization()
            );

            $data['product_name'] = $productName ?? $lineItem->getFreeFormProduct();
            $data['product_sku'] = $lineItem->getProductSku();
            $data['quantity'] = $lineItem->getQuantity();
            $data['unit'] = $lineItem->getProductUnit();
            $data['price'] = $lineItem->getPrice();
            $data['comment'] = $lineItem->getComment();
            $data['ship_by'] = $lineItem->getShipBy();
            $data['id'] = $lineItem->getEntityIdentifier();
            $data['subtotal'] = Price::create(
                $this->getLineItemSubtotalProvider()->getRowTotal($lineItem, $order->getCurrency()),
                $order->getCurrency()
            );
            $lineItems[] = $data;
        }
        $result['lineItems'] = $lineItems;

        $result['subtotals'] = $this->getSubtotals($order);
        $result['total'] = $this->getTotal($order);

        return $result;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    protected function getSubtotals(Order $order)
    {
        $result = [];
        $subtotals = $this->getTotalsProvider()->getSubtotals($order);
        foreach ($subtotals as $subtotal) {
            $result[] = [
                'label' => $subtotal->getLabel(),
                'totalPrice' => Price::create(
                    $subtotal->getSignedAmount(),
                    $subtotal->getCurrency()
                )
            ];
        }

        return $result;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    protected function getTotal(Order $order)
    {
        $total = $this->getTotalsProvider()->getTotal($order);

        return [
            'label' => $total->getLabel(),
            'totalPrice' => $total->getTotalPrice()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            TotalProcessorProvider::class,
            LineItemSubtotalProvider::class,
            LocalizationHelper::class,
            EntityNameResolver::class,
        ];
    }
}
