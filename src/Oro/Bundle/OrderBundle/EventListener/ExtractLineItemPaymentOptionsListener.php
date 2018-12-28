<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

/**
 * Converted items from order to payment line item option model
 */
class ExtractLineItemPaymentOptionsListener
{
    /** @var HtmlTagHelper */
    private $htmlTagHelper;

    /** @var UserLocalizationManager */
    private $userLocalizationManager;

    /**
     * @param HtmlTagHelper $htmlTagHelper
     * @param UserLocalizationManager $userLocalizationManager
     */
    public function __construct(HtmlTagHelper $htmlTagHelper, UserLocalizationManager $userLocalizationManager)
    {
        $this->htmlTagHelper = $htmlTagHelper;
        $this->userLocalizationManager = $userLocalizationManager;
    }

    /**
     * @param ExtractLineItemPaymentOptionsEvent $event
     */
    public function onExtractLineItemPaymentOptions(ExtractLineItemPaymentOptionsEvent $event)
    {
        $entity = $event->getEntity();
        $lineItems = $entity->getLineItems();
        $localization = $this->userLocalizationManager->getCurrentLocalization();

        foreach ($lineItems as $lineItem) {
            if (!$lineItem instanceof OrderLineItem) {
                continue;
            }

            $product = $lineItem->getProduct();

            if (!$product) {
                continue;
            }

            $lineItemModel = new LineItemOptionModel();
            $name = implode(' ', array_filter([$product->getSku(), (string)$product->getName($localization)]));
            $description = $this->htmlTagHelper->stripTags((string)$product->getShortDescription($localization));
            $lineItemModel
                ->setName($name)
                ->setDescription($description)
                ->setCost($lineItem->getValue())
                ->setQty($lineItem->getQuantity())
                ->setCurrency($lineItem->getCurrency())
                ->setUnit($lineItem->getProductUnitCode());

            $event->addModel($lineItemModel);
        }
    }
}
