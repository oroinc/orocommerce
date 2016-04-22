<?php

namespace OroB2B\Bundle\SaleBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Handler\OrderCurrencyHandler;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteAddress;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteToOrderConverter
{
    const FIELD_OFFER = 'offer';
    const FIELD_QUANTITY = 'quantity';

    /** @var OrderCurrencyHandler */
    protected $orderCurrencyHandler;

    /** @var LineItemSubtotalProvider */
    protected $lineItemSubtotalProvider;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var TotalProcessorProvider */
    protected $totalProvider;

    /**
     * @param OrderCurrencyHandler $orderCurrencyHandler
     * @param LineItemSubtotalProvider $lineItemSubtotalProvider
     * @param TotalProcessorProvider $totalProvider,
     * @param ManagerRegistry $registry
     */
    public function __construct(
        OrderCurrencyHandler $orderCurrencyHandler,
        LineItemSubtotalProvider $lineItemSubtotalProvider,
        TotalProcessorProvider $totalProvider,
        ManagerRegistry $registry
    ) {
        $this->orderCurrencyHandler = $orderCurrencyHandler;
        $this->lineItemSubtotalProvider = $lineItemSubtotalProvider;
        $this->totalProvider = $totalProvider;
        $this->registry = $registry;
    }

    /**
     * @param Quote $quote
     * @param AccountUser|null $user
     * @param array|null $selectedOffers
     * @param bool $needFlush
     * @return Order
     */
    public function convert(Quote $quote, AccountUser $user = null, array $selectedOffers = null, $needFlush = false)
    {
        $order = $this->createOrder($quote, $user);

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
        if ($quote->getShippingEstimate() !== null) {
            $this->fillShippingCost($quote->getShippingEstimate(), $order);
        }
        $this->fillSubtotals($order);

        if ($needFlush) {
            $manager = $this->registry->getManagerForClass('OroB2BOrderBundle:Order');
            $manager->persist($order);
            $manager->flush($order);
        }

        return $order;
    }

    /**
     * @param Quote $quote
     * @param AccountUser|null $user
     * @return Order
     */
    protected function createOrder(Quote $quote, AccountUser $user = null)
    {
        $accountUser = $user ?: $quote->getAccountUser();
        $account = $user ? $user->getAccount() : $quote->getAccount();
        $orderShippingAddress = $this->createOrderAddress($quote->getShippingAddress());

        $order = new Order();
        $order
            ->setAccount($account)
            ->setAccountUser($accountUser)
            ->setOwner($quote->getOwner())
            ->setOrganization($quote->getOrganization())
            ->setPoNumber($quote->getPoNumber())
            ->setShipUntil($quote->getShipUntil())
            ->setShippingAddress($orderShippingAddress)
            ->setSourceEntityClass(ClassUtils::getClass($quote))
            ->setSourceEntityId($quote->getId())
            ->setSourceEntityIdentifier($quote->getPoNumber());

        return $order;
    }

    /**
     * @param QuoteAddress|null $quoteAddress
     *
     * @return null|OrderAddress
     */
    protected function createOrderAddress(QuoteAddress $quoteAddress = null)
    {
        $orderAddress = null;

        if ($quoteAddress) {
            $orderAddress = new OrderAddress();

            $orderAddress->setAccountAddress($quoteAddress->getAccountAddress());
            $orderAddress->setAccountUserAddress($quoteAddress->getAccountUserAddress());
            $orderAddress->setLabel($quoteAddress->getLabel());
            $orderAddress->setStreet($quoteAddress->getStreet());
            $orderAddress->setStreet2($quoteAddress->getStreet2());
            $orderAddress->setCity($quoteAddress->getCity());
            $orderAddress->setPostalCode($quoteAddress->getPostalCode());
            $orderAddress->setOrganization($quoteAddress->getOrganization());
            $orderAddress->setRegionText($quoteAddress->getRegionText());
            $orderAddress->setNamePrefix($quoteAddress->getNamePrefix());
            $orderAddress->setFirstName($quoteAddress->getFirstName());
            $orderAddress->setMiddleName($quoteAddress->getMiddleName());
            $orderAddress->setLastName($quoteAddress->getLastName());
            $orderAddress->setNameSuffix($quoteAddress->getNameSuffix());
            $orderAddress->setRegion($quoteAddress->getRegion());
            $orderAddress->setCountry($quoteAddress->getCountry());
            $orderAddress->setPhone($quoteAddress->getPhone());
            $orderAddress->setFromExternalSource(true);
        }

        return $orderAddress;
    }

    /**
     * @param QuoteProductOffer $quoteProductOffer
     * @param float|null $quantity
     * @return OrderLineItem
     */
    protected function createOrderLineItem(QuoteProductOffer $quoteProductOffer, $quantity = null)
    {
        $quoteProduct = $quoteProductOffer->getQuoteProduct();
        $freeFormTitle = null;
        $productSku = null;

        if ($quoteProduct->isTypeNotAvailable()) {
            $product = $quoteProduct->getProductReplacement();
            if ($quoteProduct->isProductReplacementFreeForm()) {
                $freeFormTitle = $quoteProduct->getFreeFormProductReplacement();
                $productSku = $quoteProduct->getProductReplacementSku();
            }
        } else {
            $product = $quoteProduct->getProduct();
            if ($quoteProduct->isProductFreeForm()) {
                $freeFormTitle = $quoteProduct->getFreeFormProduct();
                $productSku = $quoteProduct->getProductSku();
            }
        }

        $orderLineItem = new OrderLineItem();
        $orderLineItem
            ->setFreeFormProduct($freeFormTitle)
            ->setProductSku($productSku)
            ->setProduct($product)
            ->setProductUnit($quoteProductOffer->getProductUnit())
            ->setQuantity($quantity ?: $quoteProductOffer->getQuantity())
            ->setPriceType($quoteProductOffer->getPriceType())
            ->setPrice($quoteProductOffer->getPrice())
            ->setFromExternalSource(true);

        return $orderLineItem;
    }

    /**
     * @param Order $order
     */
    protected function fillSubtotals(Order $order)
    {
        $subtotal = $this->lineItemSubtotalProvider->getSubtotal($order);
        $total = $this->totalProvider->getTotal($order);

        if ($subtotal) {
            $order->setSubtotal($subtotal->getAmount());
        }
        if ($total) {
            $order->setTotal($total->getAmount());
        }
    }

    /**
     * @param Price $shippingEstimate
     * @param Order $order
     */
    protected function fillShippingCost(Price $shippingEstimate, Order $order)
    {
        $shippingCostAmount = $shippingEstimate->getValue();
        $shippingEstimateCurrency = $shippingEstimate->getCurrency();
        $orderCurrency = $order->getCurrency();
        if ($orderCurrency !== $shippingEstimateCurrency) {
            $shippingCostAmount *= $this->getExchangeRate($shippingEstimateCurrency, $orderCurrency);
        }

        $order->setShippingCost(Price::create($shippingCostAmount, $orderCurrency));
    }

    /**
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float
     */
    protected function getExchangeRate($fromCurrency, $toCurrency)
    {
        return 1.0;
    }
}
