<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Symfony\Component\OptionsResolver\OptionsResolver;

class LineItemsDiscount extends AbstractDiscount implements DiscountProductUnitCodeAwareInterface
{
    const APPLY_TO = 'apply_to';
    const MAXIMUM_QTY = 'maximum_qty';

    const EACH_ITEM = 'each_item';
    const LINE_ITEMS_TOTAL = 'line_items_total';

    /**
     * @var string
     */
    protected $applyTo;

    /**
     * @var float
     */
    protected $maximumQty;

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

        $this->applyTo = $resolvedOptions[self::APPLY_TO];
        $this->maximumQty = $resolvedOptions[self::MAXIMUM_QTY];
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
        if (!$entity instanceof DiscountLineItem || (float)$entity->getQuantity() <= 0) {
            return 0.0;
        }

        $subTotal = (float) $entity->getSubtotal();
        $qty = (float) $entity->getQuantity();
        $actualPrice = $subTotal/$qty;
        $discountValue = (float) $this->getDiscountValue();

        if ($this->getDiscountType() === DiscountInterface::TYPE_AMOUNT) {
            $discountAmount = $this->calculateAmountType($qty, $actualPrice, $discountValue);
        } else {
            $discountAmount = $this->calculatePercentType($subTotal, $qty, $actualPrice, $discountValue);
        }

        if ($discountAmount > $subTotal) {
            $discountAmount = $subTotal;
        }

        return $discountAmount;
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptionsResolver(): OptionsResolver
    {
        $resolver = parent::getOptionsResolver();

        $resolver->setRequired(self::DISCOUNT_PRODUCT_UNIT_CODE);
        $resolver->setAllowedTypes(self::DISCOUNT_PRODUCT_UNIT_CODE, 'string');

        $resolver->setDefault(self::APPLY_TO, self::EACH_ITEM);
        $resolver->setAllowedTypes(self::APPLY_TO, ['string']);
        $resolver->setAllowedValues(
            self::APPLY_TO,
            [self::EACH_ITEM, self::LINE_ITEMS_TOTAL]
        );

        $resolver->setDefault(self::MAXIMUM_QTY, null);
        $resolver->setAllowedTypes(self::MAXIMUM_QTY, ['null', 'integer', 'float']);

        return $resolver;
    }

    /**
     * @param float $qty
     * @param float $actualPrice
     * @param float $discountValue
     * @return float
     */
    private function calculateAmountType($qty, $actualPrice, $discountValue)
    {
        if ($this->applyTo === self::EACH_ITEM) {
            if ($actualPrice < $discountValue) {
                $discountValue = $actualPrice;
            }

            if ($this->maximumQty !== null && $qty > $this->maximumQty) {
                $qty = $this->maximumQty;
            }

            return $discountValue * $qty;
        }

        return $discountValue;
    }

    /**
     * @param float $subTotal
     * @param float $qty
     * @param float $actualPrice
     * @param float $discountValue
     * @return float
     */
    private function calculatePercentType($subTotal, $qty, $actualPrice, $discountValue)
    {
        if ($this->applyTo === self::EACH_ITEM) {
            if ($this->maximumQty && $qty > $this->maximumQty) {
                $discountAmount = $actualPrice * $discountValue * $this->maximumQty;
            } else {
                $discountAmount = $actualPrice * $discountValue * $qty;
            }
        } else {
            if ($this->maximumQty && $qty > $this->maximumQty) {
                $discountAmount = $actualPrice * $discountValue * $this->maximumQty;
            } else {
                $discountAmount = $subTotal * $discountValue;
            }
        }

        return $discountAmount;
    }
}
