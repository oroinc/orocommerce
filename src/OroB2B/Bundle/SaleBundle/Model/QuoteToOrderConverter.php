<?php

namespace OroB2B\Bundle\SaleBundle\Model;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Model\OrderCurrencyHandler;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteToOrderConverter
{
    const FIELD_OFFER = 'offer';
    const FIELD_QUANTITY = 'quantity';

    /**
     * @var OrderCurrencyHandler
     */
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
            ->setAccountUser($quote->getAccountUser())
            ->setOwner($quote->getOwner())
            ->setOrganization($quote->getOrganization());

        if (!$selectedOffers) {
            foreach ($quote->getQuoteProducts() as $quoteProduct) {
                /** @var QuoteProductOffer $productOffer */
                $productOffer = $quoteProduct->getQuoteProductOffers()->first();

                $order->addLineItem($this->createOrderLineItem($productOffer));
            }
        } else {
            foreach ($selectedOffers as $selectedOffer) {
                /** @var QuoteProductOffer $offer */
                $offer = $selectedOffer[self::FIELD_OFFER];

                $order->addLineItem(
                    $this->createOrderLineItem($offer, (float)$selectedOffer[self::FIELD_QUANTITY])
                );
            }
        }

        $this->orderCurrencyHandler->setOrderCurrency($order);
        return $order;
    }

    /**
     * @param QuoteProductOffer $quoteProductOffer
     * @param float|null $quantity
     * @return OrderLineItem
     */
    protected function createOrderLineItem(QuoteProductOffer $quoteProductOffer, $quantity = null)
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
            ->setQuantity($quantity ?: $quoteProductOffer->getQuantity())
            ->setPriceType($quoteProductOffer->getPriceType())
            ->setPrice($quoteProductOffer->getPrice())
            ->setFromExternalSource(true);

        return $orderLineItem;
    }
}
