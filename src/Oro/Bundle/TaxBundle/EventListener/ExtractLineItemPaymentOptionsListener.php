<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PaymentBundle\Provider\ExtractOptionsProvider;
use Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Listener which is used to add tax line item model in case of using PayPalPayflowExpressCheckout payment method
 */
class ExtractLineItemPaymentOptionsListener
{
    /** @var TaxProviderRegistry */
    protected $taxProviderRegistry;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var TaxationSettingsProvider|null */
    protected $taxationSettingsProvider;

    /**
     * @param TranslatorInterface $translator
     * @param TaxProviderRegistry $taxProviderRegistry
     */
    public function __construct(TranslatorInterface $translator, TaxProviderRegistry $taxProviderRegistry)
    {
        $this->translator = $translator;
        $this->taxProviderRegistry = $taxProviderRegistry;
    }

    /**
     * @param ExtractLineItemPaymentOptionsEvent $event
     */
    public function onExtractLineItemPaymentOptions(ExtractLineItemPaymentOptionsEvent $event)
    {
        // Don't add tax line item in case taxes included into price already
        if ($this->taxationSettingsProvider && $this->taxationSettingsProvider->isProductPricesIncludeTax()) {
            return;
        }

        $context = $event->getContext();
        $paymentMethodType = $context[ExtractOptionsProvider::CONTEXT_PAYMENT_METHOD_TYPE] ?? null;

        /**
         * Skip listener logic in case this is not PayPalExpressCheckoutPaymentMethod
         */
        if ($paymentMethodType !== PayPalExpressCheckoutPaymentMethod::CONTEXT_PAYMENT_METHOD_TYPE) {
            return;
        }

        $entity = $event->getEntity();

        try {
            $result = $this->getProvider()->loadTax($entity);
            $taxAmount = $result->getTotal()->getTaxAmount();
        } catch (TaxationDisabledException $ex) {
            // taxation disabled
            return;
        } catch (\InvalidArgumentException $ex) {
            // could not load taxes for line item
            return;
        }

        if (abs((float)$taxAmount) <= 1e-6) {
            return;
        }

        $lineItemModel = new LineItemOptionModel();
        $lineItemModel
            ->setName($this->translator->trans('oro.tax.result.tax'))
            ->setCost($taxAmount)
            ->setQty(1);

        $event->addModel($lineItemModel);
    }

    /**
     * @param TaxationSettingsProvider $taxationSettingsProvider
     */
    public function setTaxationSettingsProvider(TaxationSettingsProvider $taxationSettingsProvider): void
    {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
    }

    /**
     * @return TaxProviderInterface
     */
    private function getProvider()
    {
        return $this->taxProviderRegistry->getEnabledProvider();
    }
}
