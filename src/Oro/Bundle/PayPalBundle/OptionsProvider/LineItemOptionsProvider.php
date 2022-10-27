<?php

namespace Oro\Bundle\PayPalBundle\OptionsProvider;

use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PaymentBundle\Provider\PaymentOrderLineItemOptionsProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Provider\TaxAmountProvider;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides payment line item options.
 */
class LineItemOptionsProvider
{
    private const DEFAULT_TAX_LINE_ITEM_QUANTITY = 1;

    /**
     * @var PaymentOrderLineItemOptionsProvider
     */
    private $paymentOrderLineItemOptionsProvider;

    /**
     * @var TaxAmountProvider
     */
    private $taxAmountProvider;

    /**
     * @var TaxationSettingsProvider
     */
    private $taxationSettingsProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LineItemOptionsFormatter
     */
    private $lineItemOptionsFormatter;

    public function __construct(
        PaymentOrderLineItemOptionsProvider $paymentOrderLineItemOptionsProvider,
        TaxAmountProvider $taxAmountProvider,
        TaxationSettingsProvider $taxationSettingsProvider,
        TranslatorInterface $translator,
        LineItemOptionsFormatter $lineItemOptionsFormatter
    ) {
        $this->paymentOrderLineItemOptionsProvider = $paymentOrderLineItemOptionsProvider;
        $this->taxAmountProvider = $taxAmountProvider;
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->translator = $translator;
        $this->lineItemOptionsFormatter = $lineItemOptionsFormatter;
    }

    /**
     * @param LineItemsAwareInterface $entity
     * @return LineItemOptionModel[]
     */
    public function getLineItemOptions(LineItemsAwareInterface $entity): array
    {
        $orderLineItemOptions = $this->paymentOrderLineItemOptionsProvider->getLineItemOptions($entity);
        $lineItemOptions = $this->addTaxLineItemOptions($entity, $orderLineItemOptions);

        return $this->lineItemOptionsFormatter->formatLineItemOptions($lineItemOptions);
    }

    /**
     * @param LineItemsAwareInterface $entity
     * @param LineItemOptionModel[] $orderLineItemOptions
     * @return array
     */
    private function addTaxLineItemOptions(LineItemsAwareInterface $entity, array $orderLineItemOptions): array
    {
        // Don't add tax line item in case taxes included into price already
        if ($this->taxationSettingsProvider->isProductPricesIncludeTax()) {
            return $orderLineItemOptions;
        }

        try {
            $taxAmount = $this->taxAmountProvider->getTaxAmount($entity);
        } catch (TaxationDisabledException $exception) {
            // Can not add any tax line items because taxation disabled
            // Must not add 0 also because it is also a tax value
            return $orderLineItemOptions;
        }

        $lineItemModel = (new LineItemOptionModel())
            ->setName($this->translator->trans('oro.tax.result.tax'))
            ->setCost($taxAmount)
            ->setQty(self::DEFAULT_TAX_LINE_ITEM_QUANTITY);

        $orderLineItemOptions[] = $lineItemModel;

        return $orderLineItemOptions;
    }
}
