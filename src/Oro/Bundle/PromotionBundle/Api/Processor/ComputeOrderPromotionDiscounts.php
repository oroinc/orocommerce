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

        $data = $context->getResult();
        if (!is_array($data)) {
            return;
        }

        /** @var Order $order */
        $order = $this->doctrineHelper->getEntityReference(
            Order::class,
            $context->getResultFieldValue('id', $data)
        );

        $discountFieldName = $context->getResultFieldName('discount');
        if ($context->isFieldRequested($discountFieldName, $data)) {
            $data[$discountFieldName] = $this->appliedDiscountsProvider
                ->getDiscountsAmountByOrder($order);
        }

        $shippingDiscountFieldName = $context->getResultFieldName('shippingDiscount');
        if ($context->isFieldRequested($shippingDiscountFieldName, $data)) {
            $data[$shippingDiscountFieldName] = $this->appliedDiscountsProvider
                ->getShippingDiscountsAmountByOrder($order);
        }

        $context->setResult($data);
    }
}
