<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

class ExtractLineItemPaymentOptionsListener
{
    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /**
     * @param HtmlTagHelper $htmlTagHelper
     */
    public function setHtmlTagHelper(HtmlTagHelper $htmlTagHelper)
    {
        $this->htmlTagHelper = $htmlTagHelper;
    }

    /**
     * @param ExtractLineItemPaymentOptionsEvent $event
     */
    public function onExtractLineItemPaymentOptions(ExtractLineItemPaymentOptionsEvent $event)
    {
        $entity = $event->getEntity();
        $lineItems = $entity->getLineItems();

        foreach ($lineItems as $lineItem) {
            if (!$lineItem instanceof OrderLineItem) {
                continue;
            }

            $product = $lineItem->getProduct();

            if (!$product) {
                continue;
            }

            $lineItemModel = new LineItemOptionModel();

            $name = implode(' ', array_filter([$product->getSku(), (string)$product->getDefaultName()]));
            $description = (string)$product->getDefaultShortDescription();
            if ($this->htmlTagHelper) {
                $description = $this->htmlTagHelper->stripTags($description);
            }

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
