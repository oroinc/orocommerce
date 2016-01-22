<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\TaxBundle\Form\DataTransformer\TaxBaseExclusionTransformer;
use OroB2B\Bundle\TaxBundle\Model\TaxBaseExclusion;

class TaxBaseExclusionTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var TaxBaseExclusionTransformer */
    protected $transformer;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transformer = new TaxBaseExclusionTransformer($this->doctrineHelper);
    }

    /**
     * @param mixed $value
     * @param array $expected
     *
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($value, $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        $country = new Country('ISO_CODE');
        $region = new Region('REG_ISO_CODE');

        return [
            [null, []],
            [[], []],
            ['string', []],
            ['string', []],
            [[new TaxBaseExclusion()], [['country' => null, 'region' => null, 'option' => null]]],
            [
                [(new TaxBaseExclusion())->setCountry($country)],
                [['country' => 'ISO_CODE', 'region' => null, 'option' => null]],
            ],
            [
                [(new TaxBaseExclusion())->setCountry($country)->setRegion($region)],
                [['country' => 'ISO_CODE', 'region' => 'REG_ISO_CODE', 'option' => null]],
            ],
            [
                [(new TaxBaseExclusion())->setCountry($country)->setOption('destination')],
                [['country' => 'ISO_CODE', 'region' => null, 'option' => 'destination']],
            ],
            [
                [(new TaxBaseExclusion())->setCountry($country)->setRegion($region)->setOption('destination')],
                [['country' => 'ISO_CODE', 'region' => 'REG_ISO_CODE', 'option' => 'destination']],
            ],
        ];
    }

    /**
     * @param mixed $value
     * @param array $expected
     *
     * @dataProvider transformDataProvider
     */
    public function testTransform($value, $expected)
    {
        $this->doctrineHelper->expects($this->any())->method('getEntityReference')
            ->willReturnCallback(
                function ($classAlias, $id) {
                    if (strpos($classAlias, 'Country')) {
                        return new Country($id);
                    }
                    if (strpos($classAlias, 'Region')) {
                        return new Region($id);
                    }

                    return null;
                }
            );

        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        $country = new Country('ISO_CODE');
        $region = new Region('REG_ISO_CODE');

        return [
            [null, []],
            [[], []],
            ['string', []],
            ['string', []],
            [[['country' => null, 'region' => null, 'option' => null]], [new TaxBaseExclusion()]],
            [
                [['country' => 'ISO_CODE', 'region' => null, 'option' => null]],
                [(new TaxBaseExclusion())->setCountry($country)],
            ],
            [
                [['country' => 'ISO_CODE', 'region' => 'REG_ISO_CODE', 'option' => null]],
                [(new TaxBaseExclusion())->setCountry($country)->setRegion($region)],
            ],
            [
                [['country' => 'ISO_CODE', 'region' => null, 'option' => 'destination']],
                [(new TaxBaseExclusion())->setCountry($country)->setOption('destination')],
            ],
            [
                [['country' => 'ISO_CODE', 'region' => 'REG_ISO_CODE', 'option' => 'destination']],
                [(new TaxBaseExclusion())->setCountry($country)->setRegion($region)->setOption('destination')],
            ],
        ];
    }
}
