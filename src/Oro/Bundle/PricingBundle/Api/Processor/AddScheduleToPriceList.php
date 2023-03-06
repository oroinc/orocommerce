<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a schedule to the owning price list to ensures that the price list is updated.
 */
class AddScheduleToPriceList implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $priceListForm = $context->findFormField('priceList');
        if (null === $priceListForm || !$priceListForm->isSubmitted()) {
            return;
        }

        /** @var PriceListSchedule $schedule */
        $schedule = $context->getData();
        $priceList = $schedule->getPriceList();
        if (null !== $priceList) {
            $priceList->addSchedule($schedule);
        }
    }
}
