<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\CouponGeneration\Generator;

use Oro\Bundle\PromotionBundle\CouponGeneration\Code\CodeGenerator;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;

class CodeGeneratorTest extends \PHPUnit\Framework\TestCase
{
    private CodeGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new CodeGenerator();
    }

    /**
     * @dataProvider generateOneDataProvider
     */
    public function testGenerateOne(CodeGenerationOptions $options, string $expectedPattern)
    {
        $this->assertMatchesRegularExpression($expectedPattern, $this->generator->generateOne($options));
    }

    public function testGenerateUnique()
    {
        $options = (new CodeGenerationOptions())
            ->setCodeType(CodeGenerationOptions::ALPHABETIC_CODE_TYPE)
            ->setCodeLength(5);

        $codes = $this->generator->generateUnique($options, 20);
        $this->assertCount(20, $codes);
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[a-zA-Z]{5}$/', $code);
        }

        $options = (new CodeGenerationOptions())
            ->setCodeType(CodeGenerationOptions::NUMERIC_CODE_TYPE)
            ->setCodeLength(2);

        $codes = $this->generator->generateUnique($options, 100000);
        $this->assertCount(100, $codes);

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[0-9]{2}$/', $code);
        }
    }

    public function generateOneDataProvider(): array
    {
        return [
            [
                'options' => (new CodeGenerationOptions())
                    ->setCodeLength(10),
                'expected' => '/^[a-zA-Z0-9]{10}$/',
            ],
            [
                'options' => (new CodeGenerationOptions())
                    ->setCodeType(CodeGenerationOptions::NUMERIC_CODE_TYPE),
                'expected' => '/^[0-9]{12}$/',
            ],
            [
                'options' => (new CodeGenerationOptions())
                    ->setCodeType(CodeGenerationOptions::ALPHABETIC_CODE_TYPE),
                'expected' => '/^[a-zA-Z]{12}$/',
            ],
            [
                'options' => (new CodeGenerationOptions())
                    ->setCodePrefix('qwe'),
                'expected' => '/^qwe[a-zA-Z0-9]{12}$/',
            ],
            [
                'options' => (new CodeGenerationOptions())
                    ->setCodeSuffix('rty'),
                'expected' => '/^[a-zA-Z0-9]{12}rty$/',
            ],
            [
                'options' => (new CodeGenerationOptions())
                    ->setDashesSequence(5),
                'expected' => '/^[a-zA-Z0-9]{5}-[a-zA-Z0-9]{5}-[a-zA-Z0-9]{2}$/',
            ],
            [
                'options' => (new CodeGenerationOptions())
                    ->setCodeLength(7)
                    ->setDashesSequence(6),
                'expected' => '/^[a-zA-Z0-9]{6}-[a-zA-Z0-9]$/',
            ],
            [
                'options' => (new CodeGenerationOptions())
                    ->setCodeLength(7)
                    ->setDashesSequence(7),
                'expected' => '/^[a-zA-Z0-9]{7}$/',
            ],
            [
                'options' => (new CodeGenerationOptions())
                    ->setCodePrefix('Hello')
                    ->setCodeSuffix('World')
                    ->setCodeType(CodeGenerationOptions::NUMERIC_CODE_TYPE)
                    ->setCodeLength(7)
                    ->setDashesSequence(2),
                'expected' => '/^Hello[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]World$/',
            ],
        ];
    }
}
