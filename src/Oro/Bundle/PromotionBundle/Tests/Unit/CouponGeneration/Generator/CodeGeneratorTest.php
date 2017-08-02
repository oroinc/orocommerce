<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\CouponGeneration\Generator;

use Oro\Bundle\PromotionBundle\CouponGeneration\Code\CodeGenerator;
use Oro\Bundle\PromotionBundle\CouponGeneration\Code\WrongAmountCodeGeneratorException;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;

class CodeGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CodeGenerator
     */
    protected $generator;

    public function setUp()
    {
        $this->generator = new CodeGenerator();
    }

    /**
     * @dataProvider generateDataProvider
     *
     * @param CodeGenerationOptions $options
     * @param $expectedPattern
     */
    public function testGenerate(CodeGenerationOptions $options, string $expectedPattern)
    {
        $this->assertRegExp($expectedPattern, $this->generator->generate($options));
    }

    public function testGenerateUnique()
    {
        $options = (new CodeGenerationOptions())
            ->setCodePrefix('Hello')
            ->setCodeSuffix('World')
            ->setCodeType(CodeGenerationOptions::NUMERIC_CODE_TYPE)
            ->setCodeLength(1);
        $codes = $this->generator->generateUnique($options, 5);
        $this->assertCount(5, $codes);
        foreach ($codes as $index => $code) {
            $this->assertEquals($index, $code);
            $this->assertRegExp('/^Hello[0-9]World$/', $code);
        }
    }

    /**
     * @dataProvider exceptionDataProvider
     *
     * @param CodeGenerationOptions $options
     * @param int $amount
     * @param string $expectedMessage
     */
    public function testGenerateUniqueException(CodeGenerationOptions $options, $amount, $expectedMessage)
    {
        $this->expectException(WrongAmountCodeGeneratorException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->generator->generateUnique($options, $amount);
    }

    public function generateDataProvider()
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
                    ->setCodePrefix('Привет')
                    ->setCodeSuffix('Медвед')
                    ->setCodeType(CodeGenerationOptions::NUMERIC_CODE_TYPE)
                    ->setCodeLength(7)
                    ->setDashesSequence(2),
                'expected' => '/^Пр-ив-ет-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]М-ед-ве-д$/',
            ]
        ];
    }

    public function exceptionDataProvider()
    {
        return [
            [
                'options' => (new CodeGenerationOptions())
                    ->setCodeLength(2)
                    ->setCodeType(CodeGenerationOptions::NUMERIC_CODE_TYPE),
                'amount' => 1234,
                'expectedMessage' => 'Cant generate 1234 of codes. Only 100 combinations available for given options',
            ],
            [
                'options' => (new CodeGenerationOptions())
                    ->setCodeLength(2)
                    ->setCodeType(CodeGenerationOptions::ALPHABETIC_CODE_TYPE),
                'amount' => 12345,
                'expectedMessage' => 'Cant generate 12345 of codes. Only 2704 combinations available for given options',
            ],
            [
                'options' => (new CodeGenerationOptions())
                    ->setCodeLength(2),
                'amount' => 12345,
                'expectedMessage' => 'Cant generate 12345 of codes. Only 3844 combinations available for given options',
            ],
        ];
    }
}
