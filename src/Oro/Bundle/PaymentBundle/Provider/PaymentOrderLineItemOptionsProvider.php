<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

/**
 * Converts items from order to payment line item option model.
 */
class PaymentOrderLineItemOptionsProvider
{
    private HtmlTagHelper $htmlTagHelper;

    private LocalizationProviderInterface $localizationProvider;

    public function __construct(HtmlTagHelper $htmlTagHelper, LocalizationProviderInterface $localizationProvider)
    {
        $this->htmlTagHelper = $htmlTagHelper;
        $this->localizationProvider = $localizationProvider;
    }

    /**
     * @param LineItemsAwareInterface $entity
     * @return LineItemOptionModel[]
     */
    public function getLineItemOptions(LineItemsAwareInterface $entity): array
    {
        $lineItems = $entity->getLineItems();
        $localization = $this->localizationProvider->getCurrentLocalization();

        $result = [];
        foreach ($lineItems as $lineItem) {
            if (!$lineItem instanceof OrderLineItem) {
                continue;
            }

            $product = $lineItem->getProduct();

            if ($product) {
                $name = implode(' ', array_filter([$product->getSku(), (string)$product->getName($localization)]));
                $description = $this->htmlTagHelper->stripTags((string)$product->getShortDescription($localization));
            } elseif ($lineItem->getFreeFormProduct()) {
                $name = implode(' ', array_filter([$lineItem->getProductSku(), $lineItem->getFreeFormProduct()]));
                $description = null;
            } else {
                continue;
            }

            $result[] = (new LineItemOptionModel())
                ->setName($name)
                ->setDescription($description)
                ->setCost($lineItem->getValue())
                ->setQty($lineItem->getQuantity())
                ->setCurrency($lineItem->getCurrency())
                ->setUnit($lineItem->getProductUnitCode());
        }

        return $result;
    }
}
