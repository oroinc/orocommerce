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
class AddPromotionDiscounts implements ProcessorInterface
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

        $discountFieldName = $context->getResultFieldName('discount');
        $shippingDiscountFieldName = $context->getResultFieldName('shippingDiscount');
        $isDiscountFieldRequested = $context->isFieldRequested($discountFieldName, $data);
        $isShippingDiscountFieldRequested = $context->isFieldRequested($shippingDiscountFieldName, $data);
        if ($isDiscountFieldRequested || $isShippingDiscountFieldRequested) {
            $order = $this->getOrder($data, $context->getResultFieldName('id'));
            if (null !== $order) {
                if ($isDiscountFieldRequested) {
                    $data[$discountFieldName] = $this->appliedDiscountsProvider
                        ->getDiscountsAmountByOrder($order);
                }
                if ($isShippingDiscountFieldRequested) {
                    $data[$shippingDiscountFieldName] = $this->appliedDiscountsProvider
                        ->getShippingDiscountsAmountByOrder($order);
                }
                $context->setResult($data);
            }
        }
    }

    /**
     * @param array       $data
     * @param string|null $orderIdFieldName
     *
     * @return Order|null
     */
    private function getOrder(array $data, ?string $orderIdFieldName): ?Order
    {
        if (!$orderIdFieldName || empty($data[$orderIdFieldName])) {
            return null;
        }

        return $this->doctrineHelper
            ->getEntityRepository(Order::class)
            ->find($data[$orderIdFieldName]);
    }
}
