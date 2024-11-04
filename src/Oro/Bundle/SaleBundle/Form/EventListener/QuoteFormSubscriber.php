<?php

namespace Oro\Bundle\SaleBundle\Form\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPricesProvider;
use Oro\Bundle\SaleBundle\Quote\Pricing\QuotePricesComparator;
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
    private ManagerRegistry $managerRegistry;

    private QuoteProductPricesProvider $quoteProductPricesProvider;

    private QuotePricesComparator $quotePricesComparator;

    private TranslatorInterface $translator;

    public function __construct(
        ManagerRegistry $managerRegistry,
        QuoteProductPricesProvider $quoteProductPricesProvider,
        QuotePricesComparator $quotePricesComparator,
        TranslatorInterface $translator
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->quoteProductPricesProvider = $quoteProductPricesProvider;
        $this->quotePricesComparator = $quotePricesComparator;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
            FormEvents::SUBMIT => 'onSubmit',
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
            $websiteRepository = $this->managerRegistry->getRepository(Website::class);
            $quote->setWebsite($websiteRepository->find($data['website']));
        }
    }

    private function setQuoteCustomer(Quote $quote, array $data)
    {
        if ($quote->getCustomer() === null && isset($data['customer']) && $data['customer']) {
            $customerRepository = $this->managerRegistry->getRepository(Customer::class);
            $quote->setCustomer($customerRepository->find($data['customer']));
        }
    }

    protected function processProductPriceChanges(FormInterface $form, Quote $quote): void
    {
        $productPrices = $this->quoteProductPricesProvider->getProductLineItemsTierPrices($quote);

        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            $product = $quoteProduct->getProduct();
            $productId = $product?->getId();
            $allowChanges = $this->isAllowedChanges($form, $product);

            foreach ($quoteProduct->getQuoteProductOffers() as $offer) {
                if (!$product) {
                    $quote->setPricesChanged(true);
                    if (!$allowChanges) {
                        $this->addFormError($form, 'oro.sale.quote.form.error.free_form_price_override');
                    }

                    break 2;
                }

                $tierPrices = $productPrices[$productId][$offer->getChecksum()] ?? [];

                if (!$this->quotePricesComparator->isPriceEqualsToMatchingListedPrice($offer, $tierPrices)) {
                    $quote->setPricesChanged(true);

                    if ($allowChanges) {
                        break 2;
                    }

                    if (!$this->isValidPrice($form, $offer, $tierPrices)) {
                        break 2;
                    }
                }
            }
        }
    }

    private function isAllowedChanges(FormInterface $form, ?Product $product): bool
    {
        $formConfig = $form->getConfig();
        $allowPricesOverride = $this->isAllowedPricesOverride($formConfig);
        $allowFreeForm = $this->isAllowedAddFreeFormItems($formConfig);

        return $allowPricesOverride && ($product || $allowFreeForm);
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
        FormInterface $form,
        QuoteProductOffer $quoteProductOffer,
        array $tierPrices
    ): bool {
        if (!$this->quotePricesComparator->isPriceOneOfListedPrices($quoteProductOffer, $tierPrices)) {
            $this->addFormError($form, 'oro.sale.quote.form.error.price_override');

            return false;
        }

        return true;
    }
}
