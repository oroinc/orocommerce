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
            [
                [new TaxBaseExclusion()],
                [
                    [
                        TaxBaseExclusion::COUNTRY => null,
                        TaxBaseExclusion::REGION => null,
                        TaxBaseExclusion::OPTION => null
                    ]
                ]
            ],
            [
                [(new TaxBaseExclusion())->setCountry($country)],
                [
                    [
                        TaxBaseExclusion::COUNTRY => 'ISO_CODE',
                        TaxBaseExclusion::REGION => null,
                        TaxBaseExclusion::OPTION => null
                    ]
                ],
            ],
            [
                [(new TaxBaseExclusion())->setCountry($country)->setRegion($region)],
                [
                    [
                        TaxBaseExclusion::COUNTRY => 'ISO_CODE',
                        TaxBaseExclusion::REGION => 'REG_ISO_CODE',
                        TaxBaseExclusion::OPTION => null
                    ]
                ],
            ],
            [
                [(new TaxBaseExclusion())->setCountry($country)->setOption('destination')],
                [
                    [
                        TaxBaseExclusion::COUNTRY => 'ISO_CODE',
                        TaxBaseExclusion::REGION => null,
                        TaxBaseExclusion::OPTION => 'destination'
                    ]
                ],
            ],
            [
                [(new TaxBaseExclusion())->setCountry($country)->setRegion($region)->setOption('destination')],
                [
                    [
                        TaxBaseExclusion::COUNTRY => 'ISO_CODE',
                        TaxBaseExclusion::REGION => 'REG_ISO_CODE',
                        TaxBaseExclusion::OPTION => 'destination'
                    ]
                ],
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
            [
                [
                    [
                        TaxBaseExclusion::COUNTRY => null,
                        TaxBaseExclusion::REGION => null,
                        TaxBaseExclusion::OPTION => null
                    ]
                ],
                [new TaxBaseExclusion()]
            ],
            [
                [
                    [
                        TaxBaseExclusion::COUNTRY => 'ISO_CODE',
                        TaxBaseExclusion::REGION => null,
                        TaxBaseExclusion::OPTION => null
                    ]
                ],
                [(new TaxBaseExclusion())->setCountry($country)],
            ],
            [
                [
                    [
                        TaxBaseExclusion::COUNTRY => 'ISO_CODE',
                        TaxBaseExclusion::REGION => 'REG_ISO_CODE',
                        TaxBaseExclusion::OPTION => null
                    ]
                ],
                [(new TaxBaseExclusion())->setCountry($country)->setRegion($region)],
            ],
            [
                [
                    [
                        TaxBaseExclusion::COUNTRY => 'ISO_CODE',
                        TaxBaseExclusion::REGION => null,
                        TaxBaseExclusion::OPTION => 'destination'
                    ]
                ],
                [(new TaxBaseExclusion())->setCountry($country)->setOption('destination')],
            ],
            [
                [
                    [
                        TaxBaseExclusion::COUNTRY => 'ISO_CODE',
                        TaxBaseExclusion::REGION => 'REG_ISO_CODE',
                        TaxBaseExclusion::OPTION => 'destination'
                    ]
                ],
                [(new TaxBaseExclusion())->setCountry($country)->setRegion($region)->setOption('destination')],
            ],
        ];
    }
}
