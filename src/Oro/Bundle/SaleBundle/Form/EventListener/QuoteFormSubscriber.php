<?php

namespace Oro\Bundle\SaleBundle\Form\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPriceProvider;
use Oro\Bundle\SaleBundle\Quote\Pricing\QuotePriceComparator;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Discards price modifications and free form inputs, if there are no permissions for those operations
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class QuoteFormSubscriber implements EventSubscriberInterface
{
    /** @var QuoteProductPriceProvider */
    protected $provider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(
        QuoteProductPriceProvider $provider,
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper
    ) {
        $this->provider = $provider;
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
            FormEvents::SUBMIT => 'onSubmit'
        ];
    }

    public function onPreSubmit(FormEvent $event)
    {
        $quote = $event->getForm()->getData();
        $data = $event->getData();

        if (!$quote instanceof Quote || !is_array($data)) {
            return;
        }

        $this->setQuoteWebsite($quote, $data);
        $this->setQuoteCustomer($quote, $data);
    }

    public function onSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $quote = $event->getData();

        if (!$quote instanceof Quote) {
            return;
        }

        $this->processProductPriceChanges($form, $quote);
    }

    private function setQuoteWebsite(Quote $quote, array $data)
    {
        if ($quote->getWebsite() === null && isset($data['website']) && $data['website']) {
            $websiteRepository = $this->doctrineHelper->getEntityRepository(Website::class);
            $quote->setWebsite($websiteRepository->find($data['website']));
        }
    }

    private function setQuoteCustomer(Quote $quote, array $data)
    {
        if ($quote->getCustomer() === null && isset($data['customer']) && $data['customer']) {
            $customerRepository = $this->doctrineHelper->getEntityRepository(Customer::class);
            $quote->setCustomer($customerRepository->find($data['customer']));
        }
    }

    protected function processProductPriceChanges(FormInterface $form, Quote $quote): void
    {
        $config = $form->getConfig();

        $allowPricesOverride = $this->isAllowedPricesOverride($config);
        $allowFreeForm = $this->isAllowedAddFreeFormItems($config);

        $priceComparator = new QuotePriceComparator($quote, $this->provider);
        $quoteProducts = $quote->getQuoteProducts();

        foreach ($quoteProducts as $quoteProduct) {
            $allowChanges = $allowPricesOverride && ($quoteProduct->getProduct() || $allowFreeForm);

            foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
                $price = $quoteProductOffer->getPrice();
                $priceChanged = !$price || $priceComparator->isQuoteProductOfferPriceChanged(
                    (string)$quoteProduct->getProductSku(),
                    $quoteProductOffer->getProductUnit(),
                    $quoteProductOffer->getQuantity(),
                    $price->getCurrency(),
                    $price->getValue()
                );

                if ($priceChanged) {
                    $quote->setPricesChanged(true);

                    if ($allowChanges) {
                        break;
                    }

                    if (!$this->isValidPrice($quote, $quoteProduct, $quoteProductOffer, $form)) {
                        break;
                    }
                }
            }
        }
    }

    protected function getTierPricesByQuote(Quote $quote): array
    {
        $products = [];
        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            if (!$quoteProduct->getProduct()) {
                continue;
            }

            $products[] = $quoteProduct->getProduct();
        }

        return $this->provider->getTierPricesForProducts($quote, $products);
    }

    /**
     * @param Quote $quote
     * @param array $quoteProducts
     *
     * @return array
     */
    protected function getTierPrices(Quote $quote, array $quoteProducts)
    {
        $productIds = array_filter(array_column($quoteProducts, 'product'));

        /** @var ProductRepository $productRepository */
        $productRepository = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);
        $products = $productRepository->findBy([
            'id' => $productIds
        ]);

        return $this->provider->getTierPricesForProducts($quote, $products);
    }

    protected function isTierProductPrice(
        Product $product,
        QuoteProductOffer $quoteProductOffer,
        array $tierPrices
    ): bool {
        /** @var ProductPriceInterface[] $productTierPrices */
        $productTierPrices = array_reverse($tierPrices[$product->getId()] ?? []);
        foreach ($productTierPrices as $tierPrice) {
            if ((float) $quoteProductOffer->getQuantity() < (float) $tierPrice->getQuantity()) {
                continue;
            }
            if (!$quoteProductOffer->getPrice()) {
                continue;
            }

            $isPriceTheSame = $quoteProductOffer->getPrice()->getCurrency() === $tierPrice->getPrice()->getCurrency()
                && $quoteProductOffer->getPrice()->getValue() == $tierPrice->getPrice()->getValue();
            $isUnitCodeTheSame = $quoteProductOffer->getProductUnitCode() === $tierPrice->getUnit()->getCode();
            if ($isPriceTheSame && $isUnitCodeTheSame) {
                return true;
            }
        }

        return false;
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
        /** @var ProductPriceInterface[] $productTierPrices */
        $productTierPrices = array_reverse($tierPrices[$productId] ?? []);
        foreach ($productTierPrices as $tierPrice) {
            if ((float) $quoteProductOfferData['quantity'] < (float) $tierPrice->getQuantity()) {
                continue;
            }
            if ($quoteProductOfferData['productUnit'] === $tierPrice->getUnit()->getCode() &&
                $quoteProductOfferData['price']['currency'] === $tierPrice->getPrice()->getCurrency() &&
                (float) $quoteProductOfferData['price']['value'] === (float) $tierPrice->getPrice()->getValue()
            ) {
                return true;
            }
        }
        return false;
    }

    protected function addFormError(FormInterface $form, string $error): void
    {
        $form->addError(new FormError($this->translator->trans($error)));
    }

    /**
     * @param FormConfigInterface $config
     * @return bool
     */
    protected function isAllowedPricesOverride(FormConfigInterface $config)
    {
        $options = $config->getOptions();

        return $options['allow_prices_override'] ?? false;
    }

    /**
     * @param FormConfigInterface $config
     * @return bool
     */
    protected function isAllowedAddFreeFormItems(FormConfigInterface $config)
    {
        $options = $config->getOptions();

        return $options['allow_add_free_form_items'] ?? false;
    }

    private function isValidPrice(
        Quote $quote,
        QuoteProduct $quoteProduct,
        QuoteProductOffer $quoteProductOffer,
        FormInterface $form
    ): bool {
        // check that overridden price in free form
        if (!$quoteProduct->getProduct()) {
            $this->addFormError($form, 'oro.sale.quote.form.error.free_form_price_override');
            return false;
        }

        $tierPrices = $this->getTierPricesByQuote($quote);

        // check that overridden price is tier
        if (!$this->isTierProductPrice($quoteProduct->getProduct(), $quoteProductOffer, $tierPrices)) {
            $this->addFormError($form, 'oro.sale.quote.form.error.price_override');
            return false;
        }

        return true;
    }
}
