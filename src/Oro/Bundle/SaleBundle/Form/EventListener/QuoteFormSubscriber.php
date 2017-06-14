<?php

namespace Oro\Bundle\SaleBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;

class QuoteFormSubscriber implements EventSubscriberInterface
{
    /** @var ProductPriceProvider */
    protected $productPriceProvider;

    /** @var PriceListTreeHandler */
    protected $treeHandler;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param ProductPriceProvider $productPriceProvider
     * @param PriceListTreeHandler $treeHandler
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ProductPriceProvider $productPriceProvider,
        PriceListTreeHandler $treeHandler,
        TranslatorInterface $translator
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->treeHandler = $treeHandler;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        /** @var array $data */
        $data = $event->getData();
        $form = $event->getForm();

        /** @var Quote $quote */
        $quote = $form->getData();
        $prices = $this->getQuotePricesValues($quote);
        $options = $form->getConfig()->getOptions();

        $productIds = array_filter(array_column($data['quoteProducts'], 'product'));
        $allowPricesOverride = $options['allow_prices_override'] ?? false;
        $allowFreeForm = $options['allow_add_free_form_items'] ?? false;
        $tierPrices = $this->getTierPrices($quote, $productIds);

        foreach ($data['quoteProducts'] as $quoteProductData) {
            foreach ($quoteProductData['quoteProductOffers'] as $quoteProductOfferData) {
                $key = sprintf(
                    '%s_%s_%s_%s',
                    $quoteProductData['productSku'],
                    $quoteProductOfferData['productUnit'],
                    $quoteProductOfferData['quantity'],
                    $quoteProductOfferData['price']['currency']
                );
                if (!isset($prices[$key]) || $prices[$key] !== (float) $quoteProductOfferData['price']['value']) {
                    $quote->setPricesChanged(true);
                    if ($allowPricesOverride && $allowFreeForm) {
                        break;
                    }
                    // check that overridden price is tier
                    if (!$quoteProductData['product'] ||
                        !$this->isTierPrice($quoteProductData['product'], $quoteProductOfferData, $tierPrices)
                    ) {
                        $form->addError(new FormError($this->translator->trans(
                            'oro.sale.quote.form.error.price_override'
                        )));
                    }
                }
            }
        }
    }

    /**
     * @param int $productId
     * @param array $quoteProductOfferData
     * @param array $tierPrices
     *
     * @return bool
     */
    protected function isTierPrice($productId, array $quoteProductOfferData, array $tierPrices)
    {
        $productTierPrices = array_reverse($tierPrices[$productId] ?? []);
        foreach ($productTierPrices as $tierPrice) {
            if ((float) $quoteProductOfferData['quantity'] < (float) $tierPrice['quantity']) {
                continue;
            }
            if ($quoteProductOfferData['productUnit'] === $tierPrice['unit'] &&
                $quoteProductOfferData['price']['currency'] === $tierPrice['currency'] &&
                (float) $quoteProductOfferData['price']['value'] === (float) $tierPrice['price']
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Quote $quote
     * @param array $productIds
     * @return array
     */
    protected function getTierPrices(Quote $quote, array $productIds)
    {
        $tierPrices = [];

        if ($productIds) {
            $priceList = $this->getPriceList($quote);
            if (!$priceList) {
                return [];
            }
            $tierPrices = $this->productPriceProvider->getPriceByPriceListIdAndProductIds(
                $priceList->getId(),
                $productIds
            );
        }

        return $tierPrices;
    }

    /**
     * @param Quote $quote
     * @return BasePriceList
     */
    protected function getPriceList(Quote $quote)
    {
        return $this->treeHandler->getPriceList(null, $quote->getWebsite());
    }

    /**
     * @param Quote $quote
     * @return array
     */
    protected function getQuotePricesValues(Quote $quote)
    {
        $prices = [];
        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
                $key = sprintf(
                    '%s_%s_%s_%s',
                    $quoteProduct->getProductSku(),
                    $quoteProductOffer->getProductUnitCode(),
                    $quoteProductOffer->getQuantity(),
                    $quoteProductOffer->getPrice()->getCurrency()
                );
                $prices[$key] = (float) $quoteProductOffer->getPrice()->getValue();
            }
        }

        return $prices;
    }
}
