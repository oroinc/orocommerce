<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\TaxBundle\Entity\ZipCode;
use OroB2B\Bundle\TaxBundle\Form\DataTransformer\ZipCodeTransformer;
use OroB2B\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;

class ZipCodeTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ZipCodeTransformer
     */
    protected $transformer;

    public function setUp()
    {
        $this->transformer = new ZipCodeTransformer();
    }

    public function tearDown()
    {
        unset($this->transformer);
    }

    /**
     * @dataProvider testTransformProvider
     * @param ZipCode[]|Collection $zipCodes
     * @param string $expected
     */
    public function testTransform($zipCodes, $expected)
    {
        if (!$zipCodes instanceof Collection) {
            $zipCodes = new ArrayCollection($zipCodes);
        }
        $this->assertEquals($expected, $this->transformer->transform($zipCodes));
    }

    /**
     * @return array
     */
    public function testTransformProvider()
    {
        return [
            'single value zip codes' => [
                'zipCodes' => [
                    ZipCodeTestHelper::getSingleValueZipCode('01000'),
                    ZipCodeTestHelper::getSingleValueZipCode('01200'),
                    ZipCodeTestHelper::getSingleValueZipCode('01050'),
                ],
                'expected' => '01000,01200,01050',
            ],
            'range zip codes' => [
                'zipCodes' => [
                    ZipCodeTestHelper::getRangeZipCode('01000', '02000'),
                    ZipCodeTestHelper::getRangeZipCode('01400', '02000'),
                    ZipCodeTestHelper::getRangeZipCode('05000', '06000'),
                ],
                'expected' => '01000-02000,01400-02000,05000-06000',
            ],
            'mixed zip codes' => [
                'zipCodes' => [
                    ZipCodeTestHelper::getSingleValueZipCode('11000'),
                    ZipCodeTestHelper::getRangeZipCode('01000', '02000'),
                    ZipCodeTestHelper::getSingleValueZipCode('21000'),
                    ZipCodeTestHelper::getRangeZipCode('01400', '02000'),
                    ZipCodeTestHelper::getRangeZipCode('05000', '06000'),
                    ZipCodeTestHelper::getSingleValueZipCode('31000'),
                ],
                'expected' => '11000,01000-02000,21000,01400-02000,05000-06000,31000',
            ],
        ];
    }

    /**
     * @dataProvider testReverseTransformProvider
     *
     * @param string $value
     * @param Collection|ZipCode[] $expectedCodes
     */
    public function testReverseTransform($value, $expectedCodes)
    {
        if (!$expectedCodes instanceof Collection) {
            $expectedCodes = new ArrayCollection($expectedCodes);
        }

        $this->assertEquals($expectedCodes, $this->transformer->reverseTransform($value));

    }

    /**
     * @return array
     */
    public function testReverseTransformProvider()
    {
        return [
            'success way' => [
                'value' => '0100-0200,0500,34600,123',
                'expectedCodes' => [
                    ZipCodeTestHelper::getRangeZipCode('0100', '0200'),
                    ZipCodeTestHelper::getSingleValueZipCode('0500'),
                    ZipCodeTestHelper::getSingleValueZipCode('34600'),
                    ZipCodeTestHelper::getSingleValueZipCode('123'),
                ],
            ],
            'empty value' => [
                'value' => null,
                'expectedCodes' => [],
            ],
            'success way (with spaces)' => [
                'value' => '0100-0200 , 0500 , 34600 , 123',
                'expectedCodes' => [
                    ZipCodeTestHelper::getRangeZipCode('0100', '0200'),
                    ZipCodeTestHelper::getSingleValueZipCode('0500'),
                    ZipCodeTestHelper::getSingleValueZipCode('34600'),
                    ZipCodeTestHelper::getSingleValueZipCode('123'),
                ],
            ],
            'half filled range' => [
                'value' => '0100-',
                'expectedCodes' => [
                    ZipCodeTestHelper::getRangeZipCode('0100', null),
                ],
            ],
            'skip empty values' => [
                'value' => ',,05710,,',
                'expectedCodes' => [
                    ZipCodeTestHelper::getSingleValueZipCode('05710'),
                ],
            ],
            'remove duplicate values' => [
                'value' => '05710,08123,05710,08123,33470-33500,33470-33500',
                'expectedCodes' => [
                    ZipCodeTestHelper::getSingleValueZipCode('05710'),
                    ZipCodeTestHelper::getSingleValueZipCode('08123'),
                    ZipCodeTestHelper::getRangeZipCode('33470', '33500'),
                ],
            ],
        ];
    }
}
