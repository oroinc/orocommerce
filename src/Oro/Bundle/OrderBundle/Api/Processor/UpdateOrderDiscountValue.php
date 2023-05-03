<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Calculates "percent" value by "amount" value and vise versa for the OrderDiscount entity.
 */
class UpdateOrderDiscountValue implements ProcessorInterface
{
    private RoundingServiceInterface $rounding;

    public function __construct(RoundingServiceInterface $rounding)
    {
        $this->rounding = $rounding;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $form = $context->getForm();
        if (!FormUtil::isSubmittedAndValid($form)) {
            return;
        }

        /** @var OrderDiscount $discount */
        $discount = $form->getData();
        switch ($discount->getType()) {
            case OrderDiscount::TYPE_AMOUNT:
                $discount->setPercent($this->calculatePercent(
                    $discount->getAmount(),
                    $discount->getOrder()->getSubtotal()
                ));
                FormUtil::ensureFieldSubmitted($form, 'percent', $context->getConfig());
                break;
            case OrderDiscount::TYPE_PERCENT:
                $discount->setAmount($this->calculateAmount(
                    $discount->getPercent(),
                    $discount->getOrder()->getSubtotal()
                ));
                FormUtil::ensureFieldSubmitted($form, 'amount', $context->getConfig());
                break;
        }
    }

    private function calculatePercent(float $amount, ?float $total): float
    {
        return $total > 0
            ? $this->rounding->round(($amount / $total) * 100.0)
            : 0.0;
    }

    private function calculateAmount(float $percent, ?float $total): float
    {
        return $total > 0
            ? $this->rounding->round(($percent * $total) / 100.0)
            : 0.0;
    }
}
