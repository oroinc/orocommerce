<?php

namespace Oro\Bundle\PromotionBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values of "discount" and "shippingDiscount" fields for Order entity.
 */
class ComputeOrderPromotionDiscounts implements ProcessorInterface
{
    /** @var AppliedDiscountsProvider */
    private $appliedDiscountsProvider;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param AppliedDiscountsProvider $appliedDiscountsProvider
     * @param DoctrineHelper           $doctrineHelper
     */
    public function __construct(
        AppliedDiscountsProvider $appliedDiscountsProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->appliedDiscountsProvider = $appliedDiscountsProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
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
            /** @var Order $order */
            $order = $this->doctrineHelper->getEntityReference(
                Order::class,
                $context->getResultFieldValue('id', $data)
            );
            if ($isDiscountRequested) {
                $data[$discountFieldName] = $this->appliedDiscountsProvider
                    ->getDiscountsAmountByOrder($order);
            }
            if ($isShippingDiscountRequested) {
                $data[$shippingDiscountFieldName] = $this->appliedDiscountsProvider
                    ->getShippingDiscountsAmountByOrder($order);
            }
        }

        $context->setData($data);
    }
}
