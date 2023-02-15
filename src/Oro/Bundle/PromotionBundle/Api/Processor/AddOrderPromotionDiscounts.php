<?php

namespace Oro\Bundle\PromotionBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds promotion discounts to "discounts" collection of Order entity.
 */
class AddOrderPromotionDiscounts implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private TranslatorInterface $translator;

    public function __construct(DoctrineHelper $doctrineHelper, TranslatorInterface $translator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $discountsFieldName = $context->getResultFieldName('discounts');
        if (!$context->isFieldRequested($discountsFieldName)) {
            return;
        }

        $orderIdFieldName = $context->getResultFieldName('id');
        if ($orderIdFieldName) {
            $context->setData($this->addPromotionDiscounts($context, $data, $orderIdFieldName, $discountsFieldName));
        }
    }

    private function addPromotionDiscounts(
        CustomizeLoadedDataContext $context,
        array $data,
        string $orderIdFieldName,
        string $discountsFieldName
    ): array {
        $ordersIds = $context->getIdentifierValues($data, $orderIdFieldName);
        $promotionDiscounts = $this->loadPromotionDiscounts($ordersIds);
        foreach ($data as $key => $item) {
            $orderId = $item[$orderIdFieldName];
            if (!empty($promotionDiscounts[$orderId])) {
                foreach ($promotionDiscounts[$orderId] as [$type, $amount]) {
                    $data[$key][$discountsFieldName][] = [
                        'type'        => 'promotion.' . $type,
                        'description' => $this->getPromotionDiscountDescription($type),
                        'amount'      => $amount
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * @param int[] $ordersIds
     *
     * @return array [order id => [[promotion type, discount amount], ...], ...]
     */
    private function loadPromotionDiscounts(array $ordersIds): array
    {
        $qb = $this->doctrineHelper
            ->createQueryBuilder(AppliedDiscount::class, 'discounts')
            ->select('orders.id AS orderId, promotions.type AS promotionType, discounts.amount AS discountAmount')
            ->innerJoin('discounts.appliedPromotion', 'promotions')
            ->innerJoin('promotions.order', 'orders')
            ->where('orders.id IN (:orderIds) AND discounts.lineItem IS NULL')
            ->setParameter('orderIds', $ordersIds);

        $result = [];
        $rows = $qb->getQuery()->getArrayResult();
        foreach ($rows as $row) {
            $result[$row['orderId']][] = [$row['promotionType'], $row['discountAmount']];
        }

        return $result;
    }

    private function getPromotionDiscountDescription(string $type): string
    {
        return $this->translator->trans(sprintf(
            'oro.promotion.discount.subtotal.%s.label',
            $type
        ));
    }
}
