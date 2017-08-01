<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Mock\CouponGeneration\Generator;

use Oro\Bundle\PromotionBundle\CouponGeneration\Generator\SimpleCodeGenerator;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;

/** This class is used to mock original code generator because in behat tests we cannot properly test random strings */
class SimpleCodeGeneratorMock extends SimpleCodeGenerator
{
    const NUMERIC_CODE_MOCK = '123456789012';
    const ALPHABETIC_CODE_MOCK = 'alphabeticcc';
    const ALPHANUMERIC_CODE_MOCK = 'alphanum1234';

    /** {@inheritdoc} */
    protected function generateRandomString(int $length, string $type): string
    {
        switch ($type) {
            case CodeGenerationOptions::NUMERIC_CODE_TYPE:
                $string = self::NUMERIC_CODE_MOCK;
                break;
            case CodeGenerationOptions::ALPHABETIC_CODE_TYPE:
                $string = self::ALPHABETIC_CODE_MOCK;
                break;
            default:
                $string = self::ALPHANUMERIC_CODE_MOCK;
        }

        return substr($string, 0, $length);
    }
}
