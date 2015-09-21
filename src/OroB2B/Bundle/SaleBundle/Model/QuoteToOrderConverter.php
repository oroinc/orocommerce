<?php

namespace OroB2B\Bundle\SaleBundle\Model;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Model\OrderCurrencyHandler;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteToOrderConverter
{
    /** @var OrderCurrencyHandler */
    protected $orderCurrencyHandler;

    /**
     * @param OrderCurrencyHandler $orderCurrencyHandler
     */
    public function __construct(OrderCurrencyHandler $orderCurrencyHandler)
    {
        $this->orderCurrencyHandler = $orderCurrencyHandler;
    }

    /**
     * @param Quote $quote
     * @param array|null $selectedOffers
     * @return Order
     */
    public function convert(Quote $quote, array $selectedOffers = null)
    {
        $order = new Order();
        $order
            ->setAccount($quote->getAccount())
            ->setAccountUser($quote->getAccountUser());

        if (!$selectedOffers) {
            foreach ($quote->getQuoteProducts() as $quoteProduct) {
                /** @var QuoteProductOffer $productOffer */
                $productOffer = $quoteProduct->getQuoteProductOffers()->first();

                $order->addLineItem($this->createOrderLineItem($productOffer));
            }
        } else {
            /** @var QuoteProductOffer $selectedOffer */
            foreach ($selectedOffers as $selectedOffer) {
                $order->addLineItem($this->createOrderLineItem($selectedOffer));
            }
        }

        $this->orderCurrencyHandler->setOrderCurrency($order);
        return $order;
    }

    /**
     * @param QuoteProductOffer $quoteProductOffer
     * @return OrderLineItem
     */
    protected function createOrderLineItem(QuoteProductOffer $quoteProductOffer)
    {
        $quoteProduct = $quoteProductOffer->getQuoteProduct();

        if ($quoteProduct->getProductReplacement()) {
            $product = $quoteProduct->getProductReplacement();
        } else {
            $product = $quoteProduct->getProduct();
        }

        $orderLineItem = new OrderLineItem();
        $orderLineItem
            ->setProduct($product)
            ->setProductUnit($quoteProductOffer->getProductUnit())
            ->setQuantity($quoteProductOffer->getQuantity())
            ->setPriceType($quoteProductOffer->getPriceType())
            ->setPrice($quoteProductOffer->getPrice())
            ->setFromExternalSource(true);

        return $orderLineItem;
    }
}
