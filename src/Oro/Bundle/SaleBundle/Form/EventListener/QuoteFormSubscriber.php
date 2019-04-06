<?php

namespace Oro\Bundle\SaleBundle\Form\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPriceProvider;
use Oro\Bundle\SaleBundle\Quote\Pricing\QuotePriceComparator;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Discards price modifications and free form inputs, if there are no permissions for those operations
 */
class QuoteFormSubscriber implements EventSubscriberInterface
{
    /** @var QuoteProductPriceProvider */
    protected $provider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param QuoteProductPriceProvider $provider
     * @param TranslatorInterface $translator
     * @param DoctrineHelper      $doctrineHelper
     */
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
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        $quote = $form->getData();
        $data = $event->getData();

        if (!$quote instanceof Quote || !is_array($data) || !isset($data['quoteProducts'])) {
            return;
        }

        $this->setQuoteWebsite($quote, $data);
        $this->setQuoteCustomer($quote, $data);

        $this->processPriceChanges($form, $quote, $data['quoteProducts']);
    }

    /**
     * @param Quote $quote
     * @param array $data
     */
    private function setQuoteWebsite(Quote $quote, array $data)
    {
        if ($quote->getWebsite() === null && isset($data['website']) && $data['website']) {
            $websiteRepository = $this->doctrineHelper->getEntityRepository(Website::class);
            $quote->setWebsite($websiteRepository->find($data['website']));
        }
    }

    /**
     * @param Quote $quote
     * @param array $data
     */
    private function setQuoteCustomer(Quote $quote, array $data)
    {
        if ($quote->getCustomer() === null && isset($data['customer']) && $data['customer']) {
            $customerRepository = $this->doctrineHelper->getEntityRepository(Customer::class);
            $quote->setCustomer($customerRepository->find($data['customer']));
        }
    }

    /**
     * @param FormInterface $form
     * @param Quote $quote
     * @param array $quoteProducts
     */
    protected function processPriceChanges(FormInterface $form, Quote $quote, array $quoteProducts)
    {
        $config = $form->getConfig();

        $allowPricesOverride = $this->isAllowedPricesOverride($config);
        $allowFreeForm = $this->isAllowedAddFreeFormItems($config);

        $priceComparator = new QuotePriceComparator($quote, $this->provider);

        foreach ($quoteProducts as $productData) {
            $allowChanges = $allowPricesOverride && ($productData['product'] || $allowFreeForm);
            $offers = $productData['quoteProductOffers'] ?? [];

            foreach ($offers as $productOfferData) {
                $priceChanged = $priceComparator->isQuoteProductOfferPriceChanged(
                    $productData['productSku'],
                    $productOfferData['productUnit'],
                    $productOfferData['quantity'],
                    $productOfferData['price']['currency'],
                    $productOfferData['price']['value']
                );

                if ($priceChanged) {
                    $quote->setPricesChanged(true);

                    if ($allowChanges) {
                        break;
                    }

                    // check that overridden price in free form
                    if (!$productData['product']) {
                        $this->addFormError($form, 'oro.sale.quote.form.error.free_form_price_override');
                        break;
                    }

                    $tierPrices = $this->getTierPrices($quote, $quoteProducts);
                    // check that overridden price is tier
                    if (!$this->isTierPrice($productData['product'], $productOfferData, $tierPrices)) {
                        $this->addFormError($form, 'oro.sale.quote.form.error.price_override');
                        break;
                    }
                }
            }
        }
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

    /**
     * @param FormInterface $form
     * @param string $error
     */
    protected function addFormError(FormInterface $form, $error)
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
}
