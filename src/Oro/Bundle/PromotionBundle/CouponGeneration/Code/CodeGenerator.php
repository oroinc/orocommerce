<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration\Code;

use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;

/**
 * This class is used for generating Coupon code by given options.
 */
class CodeGenerator implements CodeGeneratorInterface
{
    const DASH_SYMBOL = '-';

    const TEMPLATES = [
        CodeGenerationOptions::NUMERIC_CODE_TYPE => self::NUMERIC_TEMPLATE,
        CodeGenerationOptions::ALPHABETIC_CODE_TYPE => self::ALPHABETIC_TEMPLATE,
        CodeGenerationOptions::ALPHANUMERIC_CODE_TYPE => self::ALPHANUMERIC_TEMPLATE,
    ];

    const NUMERIC_TEMPLATE = '0123456789';
    const ALPHABETIC_TEMPLATE = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const ALPHANUMERIC_TEMPLATE = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * {@inheritdoc}
     */
    public function generateOne(CodeGenerationOptions $options): string
    {
        $string = $this->generateRandomString($options->getCodeLength(), $options->getCodeType());

        if ($options->getDashesSequence()) {
            $string = implode(static::DASH_SYMBOL, str_split($string, $options->getDashesSequence()));
        }

        return $options->getCodePrefix() . $string . $options->getCodeSuffix();
    }

    /**
     * {@inheritdoc}
     */
    public function generateUnique(CodeGenerationOptions $options, int $count): array
    {
        $codes = [];
        $count = min($count, $this->getMaxPossibleNumber($options));

        while (count($codes) < $count) {
            $code = $this->generateOne($options);
            $codes[$code] = $code;
        }

        return array_values($codes);
    }

    protected function generateRandomString(int $length, string $type): string
    {
        if ($length === 0) {
            return '';
        }
        $template = $this->getTemplate($type);
        $max = mb_strlen($template) - 1;
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $template[random_int(0, $max)];
        }

        return $randomString;
    }

    protected function getTemplate(string $type): string
    {
        if (!array_key_exists($type, self::TEMPLATES)) {
            throw new \InvalidArgumentException('Unknown code type: ' . $type);
        }

        return self::TEMPLATES[$type];
    }

    /**
     * @param CodeGenerationOptions $options
     * @return int
     */
    protected function getMaxPossibleNumber(CodeGenerationOptions $options)
    {
        $variantsNumber = mb_strlen($this->getTemplate($options->getCodeType()));

        return $variantsNumber ** $options->getCodeLength();
    }
}
