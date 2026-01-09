<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Configures amount option for PayPal Payflow transactions.
 *
 * Manages transaction amount and related amounts (tax, shipping, discount, etc.),
 * with automatic calculation and normalization to two decimal places.
 */
class Amount implements OptionInterface
{
    public const AMT = 'AMT';

    public const ITEMAMT = 'ITEMAMT';
    public const TAXAMT = 'TAXAMT';
    public const FREIGHTAMT = 'FREIGHTAMT';
    public const HANDLINGAMT = 'HANDLINGAMT';
    public const INSURANCEAMT = 'INSURANCEAMT';
    public const DISCOUNT = 'DISCOUNT';

    /** @var bool */
    protected $amountRequired;

    /**
     * @param bool $amountRequired
     */
    public function __construct($amountRequired = true)
    {
        $this->amountRequired = $amountRequired;
    }

    /**
     * @var array
     */
    protected $additionalAmounts = [
        Amount::ITEMAMT,
        Amount::TAXAMT,
        Amount::FREIGHTAMT,
        Amount::HANDLINGAMT,
        Amount::INSURANCEAMT,
    ];

    /**
     * @var array
     */
    protected $negativeAdditionalAmounts = [
        Amount::DISCOUNT
    ];

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $allowedTypes = ['string', 'integer', 'float'];

        if ($this->amountRequired) {
            $resolver->setRequired(Amount::AMT);
        }

        $resolver
            ->setDefined(Amount::AMT)
            ->setDefined($this->additionalAmounts)
            ->setDefined($this->negativeAdditionalAmounts)
            ->addAllowedTypes(Amount::AMT, $allowedTypes)
            ->addAllowedTypes(Amount::ITEMAMT, $allowedTypes)
            ->addAllowedTypes(Amount::TAXAMT, $allowedTypes)
            ->addAllowedTypes(Amount::FREIGHTAMT, $allowedTypes)
            ->addAllowedTypes(Amount::HANDLINGAMT, $allowedTypes)
            ->addAllowedTypes(Amount::INSURANCEAMT, $allowedTypes)
            ->addAllowedTypes(Amount::DISCOUNT, $allowedTypes)
            ->setNormalizer(
                Amount::AMT,
                function (OptionsResolver $resolver, $amount) {
                    $amounts = [];
                    foreach ($this->additionalAmounts as $key) {
                        if ($resolver->offsetExists($key)) {
                            $amounts[] = $resolver->offsetGet($key);
                        }
                    }

                    foreach ($this->negativeAdditionalAmounts as $key) {
                        if ($resolver->offsetExists($key)) {
                            $amounts[] = $resolver->offsetGet($key) * -1;
                        }
                    }

                    $amounts = array_filter($amounts);

                    if ($amounts) {
                        $amount = array_sum($amounts);
                    }

                    $floatValueNormalizer = self::getFloatValueNormalizer();

                    return $floatValueNormalizer($resolver, $amount);
                }
            );

        foreach ($this->additionalAmounts as $amount) {
            $resolver
                ->setNormalizer($amount, $this->getFloatValueNormalizer());
        }

        foreach ($this->negativeAdditionalAmounts as $amount) {
            $resolver
                ->setNormalizer($amount, $this->getFloatValueNormalizer());
        }
    }

    /**
     * Round
     * @return \Closure
     */
    public static function getFloatValueNormalizer()
    {
        return function (OptionsResolver $resolver, $value) {
            return sprintf('%.2f', $value);
        };
    }
}
