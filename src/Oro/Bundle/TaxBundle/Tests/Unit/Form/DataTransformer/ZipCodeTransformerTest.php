<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\TaxBundle\Entity\ZipCode;
use Oro\Bundle\TaxBundle\Form\DataTransformer\ZipCodeTransformer;
use Oro\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;

class ZipCodeTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ZipCodeTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = new ZipCodeTransformer();
    }

    /**
     * @dataProvider transformProvider
     */
    public function testTransform(?ZipCode $zipCode, ?ZipCode $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($zipCode));
    }

    public function transformProvider(): array
    {
        return [
            'nullable value' => [
                'value' => null,
                'expectedCode' => null,
            ],
            'single value zip codes' => [
                'zipCode' => ZipCodeTestHelper::getSingleValueZipCode('01000'),
                'expected' => ZipCodeTestHelper::getRangeZipCode('01000', null),
            ],
            'range zip codes' => [
                'zipCode' => ZipCodeTestHelper::getRangeZipCode('01000', '02000'),
                'expected' => ZipCodeTestHelper::getRangeZipCode('01000', '02000'),
            ],
        ];
    }

    /**
     * @dataProvider reverseTransformProvider
     */
    public function testReverseTransform(?ZipCode $value, ?ZipCode $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformProvider(): array
    {
        return [
            'nullable value' => [
                'value' => null,
                'expectedCode' => null,
            ],
            'same values in range' => [
                'value' => ZipCodeTestHelper::getRangeZipCode('123', '123'),
                'expectedCode' => ZipCodeTestHelper::getSingleValueZipCode('123'),
            ],
            'different values in range' => [
                'value' => ZipCodeTestHelper::getRangeZipCode('123', '234'),
                'expectedCode' => ZipCodeTestHelper::getRangeZipCode('123', '234'),
            ],
            'only first value in range' => [
                'value' => ZipCodeTestHelper::getRangeZipCode('123', null),
                'expectedCode' => ZipCodeTestHelper::getSingleValueZipCode('123'),
            ],
            'only second value in range' => [
                'value' => ZipCodeTestHelper::getRangeZipCode(null, '234'),
                'expectedCode' => ZipCodeTestHelper::getSingleValueZipCode('234'),
            ],
        ];
    }
}
