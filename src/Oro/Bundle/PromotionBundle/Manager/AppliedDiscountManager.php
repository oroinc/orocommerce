<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\Converter\AppliedDiscountConverterInterface;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;

class AppliedDiscountManager
{
    /** @var PromotionExecutor */
    protected $promotionExecutor;

    /** @var  AppliedDiscountConverterInterface */
    protected $appliedDiscountConverter;

    public function __construct(
        PromotionExecutor $promotionExecutor,
        AppliedDiscountConverterInterface $appliedDiscountConverter
    ) {
        $this->promotionExecutor = $promotionExecutor;
        $this->appliedDiscountConverter = $appliedDiscountConverter;
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getAppliedDiscounts(Order $order): array
    {
        $appliedDiscounts = [];
        $discountContext = $this->promotionExecutor->execute($order);
        $subtotalDiscounts = $discountContext->getSubtotalDiscounts();
        if ($subtotalDiscounts) {
            foreach ($subtotalDiscounts as $discount) {
                $appliedDiscounts[] = $this->appliedDiscountConverter->convert($discount)->setOrder($order);
            }
        }
        return $appliedDiscounts;
    }
}
