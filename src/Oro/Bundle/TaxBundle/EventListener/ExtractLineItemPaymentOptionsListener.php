<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Symfony\Component\Translation\TranslatorInterface;

class ExtractLineItemPaymentOptionsListener
{
    /** @var TaxProviderRegistry */
    protected $taxProviderRegistry;

    /** @var TranslatorInterface */
    protected $translator;

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
     * @return TaxProviderInterface
     */
    private function getProvider()
    {
        return $this->taxProviderRegistry->getEnabledProvider();
    }
}
