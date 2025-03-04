<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PromotionBundle\Manager\FrontendAppliedCouponManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "coupons" field for Checkout entity.
 */
class ComputeCheckoutCoupons implements ProcessorInterface
{
    private const string COUPONS_FIELD_NAME = 'coupons';

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly FrontendAppliedCouponManager $frontendAppliedCouponManager,
        private readonly LocalizationHelper $localizationHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        if (!$context->isFieldRequested(self::COUPONS_FIELD_NAME)) {
            return;
        }

        $data = $context->getData();
        $dataMap = $this->getDataMap($data);
        if ($dataMap) {
            $allAppliedCoupons = $this->loadAllAppliedCoupons($dataMap);
            foreach ($allAppliedCoupons as $checkoutId => $appliedCoupons) {
                $data[$dataMap[$checkoutId]][self::COUPONS_FIELD_NAME] = $appliedCoupons;
            }
            $context->setData($data);
        }
    }

    private function getDataMap(array $data): array
    {
        $dataMap = [];
        foreach ($data as $key => $item) {
            if ($item[self::COUPONS_FIELD_NAME]) {
                $dataMap[$item['id']] = $key;
            }
        }

        return $dataMap;
    }

    private function loadAllAppliedCoupons(array $dataMap): array
    {
        /** @var Checkout[] $checkouts */
        $checkouts = $this->doctrineHelper->createQueryBuilder(Checkout::class, 'c')
            ->select('c, ac, li, p')
            ->leftJoin('c.appliedCoupons', 'ac')
            ->leftJoin('c.lineItems', 'li')
            ->leftJoin('li.product', 'p')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', array_keys($dataMap))
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getResult();
        $checkoutMap = [];
        foreach ($checkouts as $checkout) {
            $checkoutMap[$checkout->getId()] = $checkout;
        }

        $result = [];
        foreach ($dataMap as $checkoutId => $key) {
            $result[$checkoutId] = isset($checkoutMap[$checkoutId])
                ? $this->loadAppliedCoupons($checkoutMap[$checkoutId])
                : [];
        }

        return $result;
    }

    private function loadAppliedCoupons(Checkout $checkout): array
    {
        $result = [];
        $appliedCoupons = $this->frontendAppliedCouponManager->getAppliedCoupons($checkout);
        foreach ($appliedCoupons as $appliedCoupon) {
            $promotion = $appliedCoupon->getPromotion();
            $label = $this->localizationHelper->getLocalizedValue($promotion->getLabels());
            $result[] = [
                'couponCode' => $appliedCoupon->getAppliedCoupon()->getCouponCode(),
                'description' => $label ? (string)$label : $promotion->getRule()?->getName()
            ];
        }

        return $result;
    }
}
