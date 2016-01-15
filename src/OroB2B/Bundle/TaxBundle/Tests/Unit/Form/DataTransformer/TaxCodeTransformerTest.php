<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\DataTransformer;

use Symfony\Bridge\Doctrine\RegistryInterface;

use OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Form\DataTransformer\TaxCodeTransformer;

class TaxCodeTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxCodeTransformer
     */
    protected $transformer;

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    protected function setUp()
    {
        $this->doctrine = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $this->transformer = new TaxCodeTransformer($this->doctrine, 'OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode');
    }

    protected function tearDown()
    {
        unset($this->transformer);
    }

    /**
     * @dataProvider testTransformProvider
     * @param AbstractTaxCode[]|array $taxCodes
     * @param array $expected
     */
    public function testTransform($taxCodes, $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($taxCodes));
    }

    /**
     * @return array
     */
    public function testTransformProvider()
    {
        return [
            'nullable value' => [
                'taxCodes' => null,
                'expected' => [],
            ],
            'empty value' => [
                'taxCodes' => [],
                'expected' => [],
            ],
            'correct value' => [
                'taxCodes' => [
                    $this->getProductTaxCode(10, 'BBBB'),
                    $this->getProductTaxCode(2, 'CCCC'),
                    $this->getProductTaxCode(5, 'AAAA'),
                ],
                'expected' => [10,2,5],
            ],
        ];
    }

    /**
     * @dataProvider testReverseTransformProvider
     * @param array $ids
     * @param AbstractTaxCode[]|array $expected
     */
    public function testReverseTransform($ids, $expected)
    {
        if (!empty($ids)) {
            $repository = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
                ->disableOriginalConstructor()
                ->getMock();

            $sampleTaxCodes = $this->getSampleTaxCodes();

            $repository->expects($this->once())
                ->method('findBy')
                ->willReturn($sampleTaxCodes);

            $this->doctrine->expects($this->once())
                ->method('getRepository')
                ->willReturn($repository);
        }

        $this->assertEquals($expected, $this->transformer->reverseTransform($ids));
    }

    /**
     * @return array
     */
    public function testReverseTransformProvider()
    {
        $sampleTaxCodes = $this->getSampleTaxCodes();
        return [
            'nullable value' => [
                'ids' => null,
                'expected' => [],
            ],
            'empty value' => [
                'ids' => [],
                'expected' => [],
            ],
            'correct value' => [
                'ids' => [10,2,5],
                'expected' => [
                    $sampleTaxCodes[2],
                    $sampleTaxCodes[0],
                    $sampleTaxCodes[1],
                ],
            ],
        ];
    }

    /**
     * @param integer $id
     * @param string $code
     * @return ProductTaxCode
     */
    protected function getProductTaxCode($id, $code)
    {
        $reflectionClass = new \ReflectionClass('OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode');

        $taxCode = new ProductTaxCode();
        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($taxCode, $id);
        $taxCode->setCode($code);

        return $taxCode;
    }

    protected function getSampleTaxCodes()
    {
        return [
            $this->getProductTaxCode(10, 'BBBB'),
            $this->getProductTaxCode(2, 'CCCC'),
            $this->getProductTaxCode(5, 'AAAA'),
        ];
    }
}
