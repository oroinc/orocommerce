<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\Exception\ConfiguredException;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides common functionality for promotion discount implementations.
 *
 * This base class handles discount configuration, validation, and calculation logic shared across
 * different discount types. It manages discount parameters (type, value, currency), matching products,
 * and provides option resolution with validation. Subclasses should implement
 * specific discount application logic for different contexts (e.g., line items, shipping, orders).
 */
abstract class AbstractDiscount implements DiscountInterface
{
    public const DISCOUNT_TYPE = 'discount_type';
    public const DISCOUNT_VALUE = 'discount_value';
    public const DISCOUNT_CURRENCY = 'discount_currency';

    /**
     * @var string
     */
    protected $discountType;

    /**
     * @var float
     */
    protected $discountValue;

    /**
     * @var string
     */
    protected $discountCurrency;

    /**
     * @var array|Product[]
     */
    protected $matchingProducts = [];

    /**
     * @var bool
     */
    protected $configured = false;

    /**
     * @var PromotionDataInterface
     */
    protected $promotion;

    /**
     * @var array
     */
    private $resolvedOptions;

    #[\Override]
    public function configure(array $options): array
    {
        if ($this->configured) {
            throw new ConfiguredException();
        }

        $this->configured = true;
        $resolvedOptions = $this->getResolvedOptions($options);

        $this->discountType = $resolvedOptions[self::DISCOUNT_TYPE];
        $this->discountValue = $resolvedOptions[self::DISCOUNT_VALUE];
        $this->discountCurrency = $resolvedOptions[self::DISCOUNT_CURRENCY];

        return $resolvedOptions;
    }

    #[\Override]
    public function getDiscountType(): string
    {
        return $this->discountType;
    }

    #[\Override]
    public function getDiscountValue(): float
    {
        return $this->discountValue;
    }

    #[\Override]
    public function getDiscountCurrency()
    {
        return $this->discountCurrency;
    }

    #[\Override]
    public function getMatchingProducts()
    {
        return $this->matchingProducts;
    }

    #[\Override]
    public function setMatchingProducts(array $products)
    {
        $this->matchingProducts = $products;

        return $this;
    }

    #[\Override]
    public function getPromotion()
    {
        return $this->promotion;
    }

    #[\Override]
    public function setPromotion(PromotionDataInterface $promotion)
    {
        $this->promotion = $promotion;

        return $this;
    }

    protected function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();

        $resolver->setRequired(self::DISCOUNT_TYPE);
        $resolver->setDefault(self::DISCOUNT_TYPE, self::TYPE_PERCENT);
        $resolver->setAllowedTypes(self::DISCOUNT_TYPE, 'string');
        $resolver->setAllowedValues(self::DISCOUNT_TYPE, [self::TYPE_PERCENT, self::TYPE_AMOUNT]);

        $resolver->setDefault(self::DISCOUNT_VALUE, 0.0);
        $resolver->setAllowedTypes(self::DISCOUNT_VALUE, ['numeric']);

        $resolver->setDefault(self::DISCOUNT_CURRENCY, null);
        $resolver->setAllowedTypes(self::DISCOUNT_CURRENCY, ['null', 'string']);

        $resolver->setNormalizer(
            self::DISCOUNT_VALUE,
            function (Options $options, $value) {
                return (float)$value;
            }
        );
        $resolver->setNormalizer(
            self::DISCOUNT_CURRENCY,
            function (Options $options, $value) {
                if ($options[self::DISCOUNT_TYPE] === self::TYPE_PERCENT) {
                    return null;
                }

                if (strlen($value) !== 3) {
                    throw new InvalidOptionsException('Currency code must be compatible with ISO 4217');
                }

                return $value;
            }
        );

        return $resolver;
    }

    protected function getResolvedOptions(array $options): array
    {
        if (!$this->resolvedOptions) {
            $this->resolvedOptions = $this->getOptionsResolver()->resolve($options);
        }

        return $this->resolvedOptions;
    }

    /**
     * @param float $amount
     * @return float
     */
    protected function calculateDiscountAmount($amount): float
    {
        $amount = (float)$amount;
        if ($this->getDiscountType() === DiscountInterface::TYPE_AMOUNT) {
            if ($amount > $this->getDiscountValue()) {
                return $this->getDiscountValue();
            }

            return $amount;
        }

        return $amount * $this->getDiscountValue();
    }
}
