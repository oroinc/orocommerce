<?php

namespace Oro\Bundle\PromotionBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Adds promotion discounts to "discounts" collection of Order entity.
 */
class AddOrderPromotionDiscounts implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(DoctrineHelper $doctrineHelper, TranslatorInterface $translator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data) || empty($data)) {
            return;
        }

        $discountsFieldName = $context->getResultFieldName('discounts');
        if (!$context->isFieldRequested($discountsFieldName)) {
            return;
        }

        $orderIdFieldName = $context->getResultFieldName('id');
        if ($orderIdFieldName) {
            $context->setResult(
                $this->addPromotionDiscounts($context, $data, $orderIdFieldName, $discountsFieldName)
            );
        }
    }

    /**
     * @param CustomizeLoadedDataContext $context
     * @param array                      $data
     * @param string                     $orderIdFieldName
     * @param string                     $discountsFieldName
     *
     * @return array
     */
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
                foreach ($promotionDiscounts[$orderId] as list($type, $amount)) {
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
        $qb = $this->doctrineHelper->getEntityManagerForClass(AppliedDiscount::class)
            ->createQueryBuilder()
            ->from(AppliedDiscount::class, 'discounts')
            ->innerJoin('discounts.appliedPromotion', 'promotions')
            ->innerJoin('promotions.order', 'orders')
            ->select('orders.id AS orderId, promotions.type AS promotionType, discounts.amount AS discountAmount')
            ->where('orders.id IN (:orderIds) AND discounts.lineItem IS NULL')
            ->setParameter('orderIds', $ordersIds);

        $result = [];
        $rows = $qb->getQuery()->getArrayResult();
        foreach ($rows as $row) {
            $result[$row['orderId']][] = [$row['promotionType'], $row['discountAmount']];
        }

        return $result;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private function getPromotionDiscountDescription(string $type): string
    {
        return $this->translator->trans(sprintf(
            'oro.promotion.discount.subtotal.%s.label',
            $type
        ));
    }
}
