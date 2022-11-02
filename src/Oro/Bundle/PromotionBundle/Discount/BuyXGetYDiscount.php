<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Responsible for configuration, calculation and applying "BuyXGetY" discount
 */
class BuyXGetYDiscount extends AbstractDiscount implements DiscountProductUnitCodeAwareInterface
{
    const APPLY_TO_EACH_Y = 'apply_to_each_y';
    const APPLY_TO_XY_TOTAL = 'apply_to_xy_total';
    const BUY_X = 'buy_x';
    const GET_Y = 'get_y';

    const DISCOUNT_APPLY_TO = 'discount_apply_to';
    const DISCOUNT_LIMIT = 'discount_limit';

    /**
     * @var integer
     */
    protected $buyX;

    /**
     * @var integer
     */
    protected $getY;

    /**
     * @var integer|null
     */
    protected $discountLimit;

    /**
     * @var string
     */
    protected $discountApplyTo;

    /**
     * @var string
     */
    protected $discountProductUnitCode;

    /**
     * {@inheritdoc}
     */
    public function configure(array $options): array
    {
        $resolvedOptions = parent::configure($options);

        $this->buyX = $resolvedOptions[self::BUY_X];
        $this->getY = $resolvedOptions[self::GET_Y];
        $this->discountLimit = $resolvedOptions[self::DISCOUNT_LIMIT];
        $this->discountApplyTo = $resolvedOptions[self::DISCOUNT_APPLY_TO];
        $this->discountProductUnitCode = $resolvedOptions[self::DISCOUNT_PRODUCT_UNIT_CODE];

        return $resolvedOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(DiscountContextInterface $discountContext)
    {
        foreach ($discountContext->getLineItems() as $discountLineItem) {
            foreach ($this->getMatchingProducts() as $discountMatchingProduct) {
                if ($discountLineItem->getProduct()->getId() === $discountMatchingProduct->getId()
                    && $discountLineItem->getProductUnitCode() === $this->discountProductUnitCode
                    && $discountLineItem->getQuantity() >= $this->getRequiredQuantity()
                ) {
                    $discountLineItem->addDiscount($this);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function calculate($entity): float
    {
        if (!$entity instanceof DiscountLineItem || (float)$entity->getQuantity() === 0.0) {
            return 0.0;
        }

        $applyTimes = $this->getApplyTimes($entity);
        $amountPerItem = $entity->getSubtotal() / $entity->getQuantity();

        if ($this->getDiscountType() === DiscountInterface::TYPE_AMOUNT) {
            return $this->calculateAmountType($amountPerItem, $applyTimes, $entity->getSubtotal());
        }

        return $this->calculatePercentType($amountPerItem, $applyTimes);
    }

    /**
     * @param float $amountPerItem
     * @param integer $applyTimes
     * @param float $entitySubtotal
     * @return float
     */
    protected function calculateAmountType($amountPerItem, $applyTimes, $entitySubtotal)
    {
        $discountValue = $this->getDiscountValue();
        if ($this->discountApplyTo === self::APPLY_TO_EACH_Y) {
            if ($this->getDiscountValue() > $amountPerItem) {
                $discountValue = $amountPerItem;
            }

            $discountValue *= $this->getY;
        }

        $discountTotal = (float)$discountValue * $applyTimes;
        if ($discountTotal < $entitySubtotal) {
            return $discountTotal;
        }

        return $entitySubtotal;
    }

    /**
     * @param float $amountPerItem
     * @param integer $applyTimes
     * @return float
     */
    protected function calculatePercentType($amountPerItem, $applyTimes)
    {
        if ($this->discountApplyTo === self::APPLY_TO_EACH_Y) {
            $discountValue = $this->getDiscountValue() * $this->getY * $amountPerItem;
        } else {
            $discountValue = $this->getDiscountValue() * $this->getRequiredQuantity() * $amountPerItem;
        }

        return (float)$discountValue * $applyTimes;
    }

    /**
     * @param DiscountLineItem $entity
     * @return integer|null
     */
    protected function getApplyTimes(DiscountLineItem $entity)
    {
        $applyTimes = intdiv($entity->getQuantity(), $this->getRequiredQuantity());
        if ($this->discountLimit && $applyTimes > $this->discountLimit) {
            return $this->discountLimit;
        }

        return $applyTimes;
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptionsResolver(): OptionsResolver
    {
        $resolver = parent::getOptionsResolver();

        $resolver->setRequired(self::DISCOUNT_PRODUCT_UNIT_CODE);
        $resolver->setAllowedTypes(self::DISCOUNT_PRODUCT_UNIT_CODE, 'string');

        $resolver->setDefault(self::BUY_X, 1);
        $resolver->setAllowedTypes(self::BUY_X, ['integer']);
        $resolver->setDefault(self::GET_Y, 1);
        $resolver->setAllowedTypes(self::GET_Y, ['integer']);

        $resolver->setDefault(self::DISCOUNT_LIMIT, null);
        $resolver->setAllowedTypes(self::DISCOUNT_LIMIT, ['null', 'integer']);

        $resolver->setDefault(self::DISCOUNT_APPLY_TO, self::APPLY_TO_EACH_Y);
        $resolver->setAllowedTypes(self::DISCOUNT_APPLY_TO, 'string');
        $resolver->setAllowedValues(self::DISCOUNT_APPLY_TO, [self::APPLY_TO_EACH_Y, self::APPLY_TO_XY_TOTAL]);

        return $resolver;
    }

    /**
     * @return integer
     */
    protected function getRequiredQuantity()
    {
        return $this->buyX + $this->getY;
    }
}
