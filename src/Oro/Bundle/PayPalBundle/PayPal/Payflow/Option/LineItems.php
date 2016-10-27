<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class LineItems extends AbstractOption implements OptionsDependentInterface
{
    const NAME = 'L_NAME%d';
    const DESC = 'L_DESC%d';
    const COST = 'L_COST%d';
    const QTY = 'L_QTY%d';
    const TAXAMT = 'L_TAXAMT%d';

    /**
     * {@inheritdoc}
     */
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
        for ($i = 1; $i <= $this->getLineItemCount($options); ++$i) {
            $this->configureResolver($resolver, sprintf(self::NAME, $i), ['string'], $this->getLengthNormalizer(36));
            $this->configureResolver($resolver, sprintf(self::DESC, $i), ['string'], $this->getLengthNormalizer(35));
            $this->configureResolver(
                $resolver,
                sprintf(self::COST, $i),
                ['float', 'integer', 'string'],
                Amount::getFloatValueNormalizer()
            );
            $this->configureResolver($resolver, sprintf(self::QTY, $i), ['integer']);
            $this->configureResolver(
                $resolver,
                sprintf(self::TAXAMT, $i),
                ['float', 'integer', 'string'],
                Amount::getFloatValueNormalizer()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableDependent(array $options)
    {
        return true;
    }

    /**
     * @param OptionsResolver $resolver
     * @param string $field
     * @param array $allowedTypes
     * @param \Closure|null $normalizer
     */
    protected function configureResolver(
        OptionsResolver $resolver,
        $field,
        array $allowedTypes = [],
        \Closure $normalizer = null
    ) {
        $resolver
            ->setDefined($field)
            ->addAllowedTypes($field, $allowedTypes);

        if ($normalizer) {
            $resolver->setNormalizer($field, $normalizer);
        }
    }

    /**
     * @param array $options
     * @return array
     */
    public static function prepareOptions($options)
    {
        $result = [];
        $num = 0;
        $itemSum = $taxSum = 0;

        foreach ($options as $option) {
            ++$num;
            foreach ([self::NAME, self::DESC] as $field) {
                $result[sprintf($field, $num)] = self::getValue($option, $field);
            }

            foreach ([self::COST, self::QTY, self::TAXAMT] as $field) {
                $result[sprintf($field, $num)] = self::getValue($option, $field, 0);
            }

            // TODO: Need to use bignumbers. Should be updated in BB-2369
            $itemSum += isset($option[self::COST], $option[self::QTY]) ? $option[self::COST] * $option[self::QTY] : 0;
            $taxSum += isset($option[self::TAXAMT], $option[self::QTY]) ? $option[self::TAXAMT] * $option[self::QTY]: 0;
        }

        $result[Amount::TAXAMT] = $taxSum;
        $result[Amount::ITEMAMT] = $itemSum;

        return $result;
    }

    /**
     * @param array $array
     * @param string $key
     * @param string $default
     * @return string
     */
    protected static function getValue($array, $key, $default = '')
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    /**
     * @param int $length
     * @return \Closure
     */
    protected function getLengthNormalizer($length)
    {
        return function (OptionsResolver $resolver, $value) use ($length) {
            return substr($value, 0, $length);
        };
    }

    /**
     * @param array $options
     * @return int
     */
    protected function getLineItemCount(array $options)
    {
        $nameKey = rtrim(LineItems::NAME, '%d');

        $count = 0;
        foreach ($options as $key => $value) {
            $count += strpos($key, $nameKey) === 0 ? 1 : 0;
        }

        return $count;
    }
}
