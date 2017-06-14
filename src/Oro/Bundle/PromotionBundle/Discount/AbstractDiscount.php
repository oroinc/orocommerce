<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\PromotionBundle\Discount\Exception\ConfiguredException;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractDiscount implements DiscountInterface
{
    const DISCOUNT_TYPE = 'discount_type';
    const DISCOUNT_VALUE = 'discount_value';
    const DISCOUNT_CURRENCY = 'discount_currency';

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
     * @var \Traversable
     */
    protected $matchingProducts;

    /**
     * @var bool
     */
    protected $configured = false;

    /**
     * @var array
     */
    private $resolvedOptions;

    /**
     * {@inheritdoc}
     */
    public function configure(array $options)
    {
        if ($this->configured) {
            throw new ConfiguredException();
        }

        $this->configured = true;
        $resolvedOptions = $this->getResolvedOptions($options);

        $this->discountType = $resolvedOptions[self::DISCOUNT_TYPE];
        $this->discountValue = $resolvedOptions[self::DISCOUNT_VALUE];
        $this->discountCurrency = $resolvedOptions[self::DISCOUNT_CURRENCY];
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountType(): string
    {
        return $this->discountType;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountValue(): float
    {
        return $this->discountValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountCurrency(): string
    {
        return $this->discountCurrency;
    }

    /**
     * {@inheritdoc}
     */
    public function setMatchingProducts(\Traversable $products)
    {
        $this->matchingProducts = $products;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        if ($this->getDiscountType() === self::TYPE_PERCENT) {
            return ($this->getDiscountValue() * 100) . '%';
        }

        return $this->getDiscountValue() . ' ' . $this->getDiscountCurrency();
    }

    /**
     * @return OptionsResolver
     */
    protected function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();

        $resolver->setRequired(self::DISCOUNT_TYPE);
        $resolver->setDefault(self::DISCOUNT_TYPE, self::TYPE_PERCENT);
        $resolver->setAllowedTypes(self::DISCOUNT_TYPE, 'string');
        $resolver->setAllowedValues(self::DISCOUNT_TYPE, [self::TYPE_PERCENT, self::TYPE_AMOUNT]);

        $resolver->setDefault(self::DISCOUNT_VALUE, 0.0);
        $resolver->setAllowedTypes(self::DISCOUNT_VALUE, 'float');

        $resolver->setDefault(self::DISCOUNT_CURRENCY, null);
        $resolver->setAllowedTypes(self::DISCOUNT_CURRENCY, ['null', 'string']);

        return $resolver;
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getResolvedOptions(array $options): array
    {
        if (!$this->resolvedOptions) {
            $this->resolvedOptions = $this->getOptionsResolver()->resolve($options);
        }

        return $this->resolvedOptions;
    }
}
