<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration\Generator;

use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;

class SimpleCouponGenerator implements CouponGeneratorInterface
{
    const DASHES_SYMBOL = '-';

    /**
     * {@inheritdoc}
     */
    public function generate(CodeGenerationOptions $options)
    {
        $string = $this->generateRandomString($options->getCodeLength(), $options->getCodeType());
        $string = $options->getCodePrefix() . $string . $options->getCodeSuffix();
        if (!$options->getDashesSequence()) {
            return $string;
        }
        return implode(static::DASHES_SYMBOL, $this->mbStrSplit($string, $options->getDashesSequence()));
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
        $charactersLength = mb_strlen($template);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $template[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getTemplate(string $type): string
    {
        switch ($type) {
            case CodeGenerationOptions::NUMERIC_CODE_TYPE:
                $characters = '123456789';
                break;
            case CodeGenerationOptions::ALPHABETIC_CODE_TYPE:
                $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            default:
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        return $characters;
    }

    /**
     * @param string $string
     * @param int $length
     * @return array
     */
    protected function mbStrSplit(string $string, int $length): array
    {
        $parts = [];
        $len = mb_strlen($string, 'UTF-8');
        for ($i = 0; $i < $len; $i += $length) {
            $parts[] = mb_substr($string, $i, $length);
        }
        return $parts;
    }
}
