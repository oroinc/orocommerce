<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Calculates totals, subtotals and discounts for an order and its line items.
 */
class FillOrderTotals implements ProcessorInterface
{
    /** @var TotalHelper */
    private $totalHelper;

    /**
     * @param TotalHelper $totalHelper
     */
    public function __construct(TotalHelper $totalHelper)
    {
        $this->totalHelper = $totalHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->getForm()->isValid()) {
            return;
        }

        $this->totalHelper->fill($context->getData());
    }
}
