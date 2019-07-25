<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a discount to the order and recalculate order total discounts.
 */
class AddDiscountToOrder implements ProcessorInterface
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

        /** @var OrderDiscount $discount */
        $discount = $context->getData();
        $order = $discount->getOrder();
        if (null !== $order) {
            $order->addDiscount($discount);
            $this->totalHelper->fillDiscounts($order);
        }
    }
}
