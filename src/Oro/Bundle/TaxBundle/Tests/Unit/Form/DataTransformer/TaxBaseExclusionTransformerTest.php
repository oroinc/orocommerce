<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Factory\TaxBaseExclusionFactory;
use Oro\Bundle\TaxBundle\Form\DataTransformer\TaxBaseExclusionTransformer;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;

class TaxBaseExclusionTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TaxBaseExclusionFactory */
    private $taxBaseExclusionFactory;

    /** @var TaxBaseExclusionTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->taxBaseExclusionFactory = $this->createMock(TaxBaseExclusionFactory::class);

        $this->transformer = new TaxBaseExclusionTransformer($this->taxBaseExclusionFactory);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(array|string|null $value, array $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
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
     * @dataProvider transformDataProvider
     */
    public function testTransform(array|string|null $value, array $expected)
    {
        $this->taxBaseExclusionFactory->expects($this->any())
            ->method('create')
            ->willReturnCallback(function ($value) {
                $exclusion = new TaxBaseExclusion();
                if (!empty($value[TaxBaseExclusion::COUNTRY])) {
                    $exclusion->setCountry(new Country($value[TaxBaseExclusion::COUNTRY]));
                }
                if (!empty($value[TaxBaseExclusion::REGION])) {
                    $exclusion->setRegion(new Region($value[TaxBaseExclusion::REGION]));
                }
                if (!empty($value[TaxBaseExclusion::OPTION])) {
                    $exclusion->setOption($value[TaxBaseExclusion::OPTION]);
                }

                return $exclusion;
            });

        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    public function transformDataProvider(): array
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
