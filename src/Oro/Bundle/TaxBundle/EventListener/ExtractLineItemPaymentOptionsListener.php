<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Manager\TaxManager;

class ExtractLineItemPaymentOptionsListener
{
    /** @var TaxManager */
    protected $taxManager;

    /**
     * @param TranslatorInterface $translator
     * @param TaxManager $taxManager
     */
    public function __construct(TranslatorInterface $translator, TaxManager $taxManager)
    {
        $this->translator = $translator;
        $this->taxManager = $taxManager;
    }

    /**
     * @param ExtractLineItemPaymentOptionsEvent $event
     */
    public function onExtractLineItemPaymentOptions(ExtractLineItemPaymentOptionsEvent $event)
    {
        $entity = $event->getEntity();

        try {
            $result = $this->taxManager->loadTax($entity);
            $taxAmount = $result->getTotal()->getTaxAmount();
        } catch (TaxationDisabledException $ex) {
            // taxation disabled
            return;
        } catch (\InvalidArgumentException $ex) {
            // could not load taxes for line item
            return;
        }

        if ($taxAmount === 0) {
            return;
        }

        $lineItemModel = new LineItemOptionModel();
        $lineItemModel
            ->setName($this->translator->trans('oro.tax.result.tax'))
            ->setCost($taxAmount)
            ->setQty(1);

        $event->addModel($lineItemModel);
    }
}
