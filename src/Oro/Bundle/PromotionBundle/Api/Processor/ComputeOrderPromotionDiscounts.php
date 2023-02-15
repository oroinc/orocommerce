<?php

namespace Oro\Bundle\PromotionBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values of "discount" and "shippingDiscount" fields for Order entity.
 */
class ComputeOrderPromotionDiscounts implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $discountFieldName = $context->getResultFieldName('discount');
        $shippingDiscountFieldName = $context->getResultFieldName('shippingDiscount');

        $isDiscountRequested = $context->isFieldRequested($discountFieldName, $data);
        $isShippingDiscountRequested = $context->isFieldRequested($shippingDiscountFieldName, $data);
        if (!$isDiscountRequested && !$isShippingDiscountRequested) {
            return;
        }

        if ($context->getResultFieldValue('disablePromotions', $data)) {
            if ($isDiscountRequested) {
                $data[$discountFieldName] = null;
            }
            if ($isShippingDiscountRequested) {
                $data[$shippingDiscountFieldName] = null;
            }
        } else {
            if ($isDiscountRequested) {
                $data[$discountFieldName] = $this->getDiscountsAmountByOrder($data, $context);
            }
            if ($isShippingDiscountRequested) {
                $data[$shippingDiscountFieldName] = $this->getShippingDiscountsAmountByOrder($data, $context);
            }
        }

        $context->setData($data);
    }

    /**
     * @see \Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider::getDiscountsAmountByOrder
     */
    private function getDiscountsAmountByOrder(array $order, CustomizeLoadedDataContext $context): float
    {
        $amount = 0.0;
        $appliedPromotionsFieldName = $context->getResultFieldName('appliedPromotions');
        foreach ($order[$appliedPromotionsFieldName] as $appliedPromotion) {
            if (ShippingDiscount::NAME === $appliedPromotion['type']) {
                continue;
            }
            $amount += $this->getDiscountsSum($appliedPromotion);
        }

        return $amount;
    }

    /**
     * @see \Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider::getShippingDiscountsAmountByOrder
     */
    private function getShippingDiscountsAmountByOrder(array $order, CustomizeLoadedDataContext $context): float
    {
        $amount = 0.0;
        $appliedPromotionsFieldName = $context->getResultFieldName('appliedPromotions');
        foreach ($order[$appliedPromotionsFieldName] as $appliedPromotion) {
            if (ShippingDiscount::NAME !== $appliedPromotion['type']) {
                continue;
            }
            $amount += $this->getDiscountsSum($appliedPromotion);
        }

        return $amount;
    }

    private function getDiscountsSum(array $appliedPromotion): float
    {
        $amount = 0.0;
        foreach ($appliedPromotion['appliedDiscounts'] as $appliedDiscount) {
            $amount += $appliedDiscount['amount'];
        }

        return $amount;
    }
}
