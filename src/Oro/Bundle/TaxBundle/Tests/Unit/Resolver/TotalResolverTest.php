<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Resolver\RoundingResolver;
use Oro\Bundle\TaxBundle\Resolver\TotalResolver;
use Oro\Bundle\TaxBundle\Tests\ResultComparatorTrait;

class TotalResolverTest extends \PHPUnit_Framework_TestCase
{
    use ResultComparatorTrait;

    /** @var TotalResolver */
    protected $resolver;

    /**
     * @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $settingsProvider;

    protected function setUp()
    {
        $this->settingsProvider = $this->getMockBuilder('Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()->getMock();

        $this->resolver = new TotalResolver($this->settingsProvider, new RoundingResolver());
    }

    public function testResolveEmptyItems()
    {
        $taxable = new Taxable();

        $this->resolver->resolve($taxable);

        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\Result', $taxable->getResult());
        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\ResultElement', $taxable->getResult()->getTotal());
        $this->compareResult([], $taxable->getResult());
    }

    public function testResolveLockedResult()
    {
        $taxable = new Taxable();
        $taxable->addItem(new Taxable());
        $taxable->getResult()->lockResult();

        $this->resolver->resolve($taxable);

        $this->assertNull($taxable->getResult()->getOffset(Result::TOTAL));
        $this->assertNull($taxable->getResult()->getOffset(Result::TAXES));
    }

    /**
     * @param array $items
     * @param ResultElement $expectedTotalResult
     * @param array $expectedTaxes
     * @param bool $startOnTotal
     * @param bool $priceInclTax
     * @dataProvider resolveDataProvider
     */
    public function testResolve(
        array $items,
        ResultElement $expectedTotalResult,
        array $expectedTaxes,
        $startOnTotal = true,
        $priceInclTax = false
    ) {
        $this->settingsProvider->expects($this->any())->method('isStartCalculationOnItem')
            ->willReturn(!$startOnTotal);
        $this->settingsProvider->expects($this->any())->method('isStartCalculationOnTotal')
            ->willReturn($startOnTotal);
        $this->settingsProvider->expects($this->any())->method('isProductPricesIncludeTax')
            ->willReturn($priceInclTax);

        $taxable = new Taxable();
        foreach ($items as $item) {
            $itemTaxable = new Taxable();
            $itemTaxable->setResult(new Result($item));
            $taxable->addItem($itemTaxable);
        }

        $this->resolver->resolve($taxable);

        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\Result', $taxable->getResult());
        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\ResultElement', $taxable->getResult()->getTotal());
        $this->assertEquals($expectedTotalResult, $taxable->getResult()->getTotal());
        $this->assertEquals($expectedTaxes, $taxable->getResult()->getTaxes());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function resolveDataProvider()
    {
        return [
            'plain' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('24.1879', '19.99', '4.1979', '0.0021'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.5992'),
                            TaxResultElement::create('2', '0.07', '19.99', '1.3993'),
                            TaxResultElement::create('3', '0.06', '19.99', '1.1994'),
                        ],
                    ],
                ],
                'expectedTotalResult' => ResultElement::create('24.19', '19.99', '4.2', '0.0021'),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.08', '19.99', '1.5992'),
                    TaxResultElement::create('2', '0.07', '19.99', '1.3993'),
                    TaxResultElement::create('3', '0.06', '19.99', '1.1994'),
                ],
            ],
            'multiple items same tax' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('21.5892', '19.99', '1.5992', '0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.5992'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('23.7492', '21.99', '1.7592', '0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '21.99', '1.7592'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('25.9092', '23.99', '1.9192', '0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '23.99', '1.9192'),
                        ],
                    ],
                ],
                'expectedTotalResult' => ResultElement::create('71.25', '65.97', '5.28', '0.0024'),
                'expectedTaxes' => [TaxResultElement::create('1', '0.08', '65.97', '5.2776')],
            ],
            'tax excluded, start from total' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('22.035', '19.50', '2.535', '0.0013'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.50', '1.365'),
                            TaxResultElement::create('2', '0.05', '19.50', '0.975'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('25.0686', '21.99', '3.0786', '0.0014'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '21.99', '1.7592'),
                            TaxResultElement::create('3', '0.06', '21.99', '1.3194'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('28.0683', '23.97', '4.0749', '0.0017'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '23.97', '1.9176'),
                            TaxResultElement::create('4', '0.09', '23.97', '2.1573'),
                        ],
                    ],
                ],
                'expectedTotalResult' => ResultElement::create(
                    '75.1763', // 22.035 + 25.0686 + 28.0683 + 0.0013 + 0.0014 + 0.0017
                    '65.46',
                    '9.6929', // 2.535 + 3.0786 + 4.0749 + 0.0013 + 0.0014 + 0.0017
                    '0.0044'
                ),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.08', '65.46', '5.0418'),
                    TaxResultElement::create('2', '0.05', '19.50', '0.975'),
                    TaxResultElement::create('3', '0.06', '21.99', '1.3194'),
                    TaxResultElement::create('4', '0.09', '23.97', '2.1573'),
                ],
            ],
            'tax excluded, start from item' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('22.035', '19.50', '2.535', '0.0013'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.50', '1.365'),
                            TaxResultElement::create('2', '0.05', '19.50', '0.975'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('25.0686', '21.99', '3.0786', '0.0014'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '21.99', '1.7592'),
                            TaxResultElement::create('3', '0.06', '21.99', '1.3194'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('28.0683', '23.97', '4.0749', '0.0017'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '23.97', '1.9176'),
                            TaxResultElement::create('4', '0.09', '23.97', '2.1573'),
                        ],
                    ],
                ],
                'expectedTotalResult' => ResultElement::create(
                    '75.18', // 22.04 + 25.07 + 28.07
                    '65.46',
                    '9.69',
                    '0.0044'
                ),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.08', '65.46', '5.0418'),
                    TaxResultElement::create('2', '0.05', '19.50', '0.975'),
                    TaxResultElement::create('3', '0.06', '21.99', '1.3194'),
                    TaxResultElement::create('4', '0.09', '23.97', '2.1573'),
                ],
                'startOnTotal' => false,
            ],
            'tax included, start from total' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('19.50', '19.2497', '0.2503', '0.0003'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.2497', '1.3475'),
                            TaxResultElement::create('2', '0.05', '19.2497', '0.9625'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('21.99', '21.6863', '0.3037', '0.0037'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '21.6863', '1.7349'),
                            TaxResultElement::create('3', '0.06', '21.6863', '1.3012'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('23.15', '22.7630', '0.3870', '-0.003'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '22.7630', '1.821'),
                            TaxResultElement::create('4', '0.09', '22.7630', '2.049'),
                        ],
                    ],
                ],
                'expectedTotalResult' => ResultElement::create(
                    '64.6410', // 19.5 + 21.99 + 23.15 + 0.0003 + 0.0037+ -0.003
                    '63.6990', // 19.2497 + 21.6863 + 22.7630
                    '0.9420', // 0.2503 + 0.3037 + 0.3870 + 0.0003 + 0.0037+ -0.003
                    '0.001'
                ),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.08', '63.6990', '4.9034'),
                    TaxResultElement::create('2', '0.05', '19.2497', '0.9625'),
                    TaxResultElement::create('3', '0.06', '21.6863', '1.3012'),
                    TaxResultElement::create('4', '0.09', '22.7630', '2.049'),
                ],
            ],
            'tax included, start from item' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('19.50', '19.2497', '0.2503', '0.0003'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.2497', '1.3475'),
                            TaxResultElement::create('2', '0.05', '19.2497', '0.9625'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('21.99', '21.6863', '0.3037', '0.0037'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '21.6863', '1.7349'),
                            TaxResultElement::create('3', '0.06', '21.6863', '1.3012'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('23.15', '22.7630', '0.3870', '-0.003'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '22.7630', '1.821'),
                            TaxResultElement::create('4', '0.09', '22.7630', '2.049'),
                        ],
                    ],
                ],
                'expectedTotalResult' => ResultElement::create(
                    '64.64', // 19.5 + 21.99 + 23.15
                    '63.70', // 19.25 + 21.69 + 22.76
                    '0.94',
                    '0.001'
                ),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.08', '63.6990', '4.9034'),
                    TaxResultElement::create('2', '0.05', '19.2497', '0.9625'),
                    TaxResultElement::create('3', '0.06', '21.6863', '1.3012'),
                    TaxResultElement::create('4', '0.09', '22.7630', '2.049'),
                ],
                'startOnTotal' => false,
            ],
            'tax included, start from total to adjust amount' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('19.50', '19.2497', '0.2503', '0.0003'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.2497', '1.3475'),
                            TaxResultElement::create('2', '0.05', '19.2497', '0.9625'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('21.99', '21.6863', '0.3037', '0.0037'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '21.6863', '1.7349'),
                            TaxResultElement::create('3', '0.06', '21.6863', '1.3012'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('23.15', '22.7630', '0.3870', '-0.003'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '22.7630', '1.821'),
                            TaxResultElement::create('4', '0.09', '22.7630', '2.049'),
                        ],
                    ],
                ],
                'expectedTotalResult' => ResultElement::create(
                    '64.64',
                    '63.6980', // 19.2497 + 21.6863 + 22.7630 - (0.0003 + 0.0037 + -0.003)
                    '0.9420', // 0.2503 + 0.3037 + 0.3870 + 0.0003 + 0.0037+ -0.003
                    '0.0010'
                ),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.08', '63.6990', '4.9034'),
                    TaxResultElement::create('2', '0.05', '19.2497', '0.9625'),
                    TaxResultElement::create('3', '0.06', '21.6863', '1.3012'),
                    TaxResultElement::create('4', '0.09', '22.7630', '2.049'),
                ],
                'startOnTotal' => true,
                'priceInclTax' => true,
            ],
            'failed' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('', ''),
                        Result::TAXES => [],
                    ],
                ],
                'expectedTotalResult' => ResultElement::create('0', '0', '0', '0'),
                'expectedTaxes' => [],
            ],
            'safe if row failed' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('21.5892', '19.99', '1.5992', '0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.5992'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('', '23.99', '1.9192', '0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '23.99', '1.9192'),
                        ],
                    ],
                ],
                'expectedTotalResult' => ResultElement::create('21.59', '19.99', '1.6', '0.0008'),
                'expectedTaxes' => [TaxResultElement::create('1', '0.08', '19.99', '1.5992')],
            ],
            'safe if applied tax failed' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('21.5892', '19.99', '1.5992', '0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.5992'),
                        ],
                    ],
                    [
                        Result::ROW => ResultElement::create('25.9092', '23.99', '1.9192', '0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '', '1.9192'),
                        ],
                    ],
                ],
                'expectedTotalResult' => ResultElement::create('21.59', '19.99', '1.6', '0.0008'),
                'expectedTaxes' => [TaxResultElement::create('1', '0.08', '19.99', '1.5992')],
            ],
        ];
    }
}
