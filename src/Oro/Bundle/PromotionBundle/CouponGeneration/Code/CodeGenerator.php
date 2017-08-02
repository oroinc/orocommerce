<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration\Code;

use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;

/**
 * This class is used for generating Coupon code by given options.
 */
class CodeGenerator implements CodeGeneratorInterface
{
    const DASHES_SYMBOL = '-';

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
    public function generate(CodeGenerationOptions $options): string
    {
        $string = $this->generateRandomString($options->getCodeLength(), $options->getCodeType());
        $string = $options->getCodePrefix() . $string . $options->getCodeSuffix();
        if (!$options->getDashesSequence()) {
            return $string;
        }
        return implode(static::DASHES_SYMBOL, $this->splitString($string, $options->getDashesSequence()));
    }

    /**
     * {@inheritdoc}
     */
    public function generateUnique(CodeGenerationOptions $options, int $amount): array
    {
        $this->validate($options, $amount);
        $codes = [];

        while (count($codes) < $amount) {
            $code = $this->generate($options);
            $codes[$code] = $code;
        }
        return $codes;
    }

    /**
     * @param int $length
     * @param string $type
     * @return string
     */
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

    /**
     * @param string $type
     * @return string
     */
    protected function getTemplate(string $type): string
    {
        if (!array_key_exists($type, self::TEMPLATES)) {
            throw new \InvalidArgumentException('Unknown code type: ' . $type);
        }
        return self::TEMPLATES[$type];
    }

    /**
     * @param string $string
     * @param int $interval
     * @return array
     */
    protected function splitString(string $string, int $interval): array
    {
        $parts = [];
        $stringLength = mb_strlen($string, 'UTF-8');
        for ($i = 0; $i < $stringLength; $i += $interval) {
            $parts[] = mb_substr($string, $i, $interval);
        }
        return $parts;
    }

    /**
     * @param CodeGenerationOptions $options
     * @param int $amount
     * @throws WrongAmountCodeGeneratorException
     */
    protected function validate(CodeGenerationOptions $options, int $amount)
    {
        $variantsNumber = mb_strlen($this->getTemplate($options->getCodeType()));
        $maxCombinations = pow($variantsNumber, $options->getCodeLength());
        if ($maxCombinations < $amount) {
            throw new WrongAmountCodeGeneratorException(
                "Cant generate $amount of codes. Only $maxCombinations combinations available for given options"
            );
        }
    }
}
