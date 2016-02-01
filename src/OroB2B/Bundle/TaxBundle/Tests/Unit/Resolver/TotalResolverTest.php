<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver;

use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;
use OroB2B\Bundle\TaxBundle\Resolver\TotalResolver;

class TotalResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var TotalResolver */
    protected $resolver;

    protected function setUp()
    {
        $this->resolver = new TotalResolver();
    }

    public function testResolveEmptyItems()
    {
        $taxable = new Taxable();

        $this->resolver->resolve($taxable);

        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $taxable->getResult());
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\ResultElement', $taxable->getResult()->getTotal());
        $this->assertEquals(new ResultElement(), $taxable->getResult()->getTotal());
    }

    /**
     * @param array $items
     * @param ResultElement $expectedTotalResult
     * @param array $expectedTaxes
     * @dataProvider resolveDataProvider
     */
    public function testResolve(array $items, ResultElement $expectedTotalResult, array $expectedTaxes)
    {
        $taxable = new Taxable();
        foreach ($items as $item) {
            $itemTaxable = new Taxable();
            $itemTaxable->setResult(new Result($item));
            $taxable->addItem($itemTaxable);
        }

        $this->resolver->resolve($taxable);

        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $taxable->getResult());
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\ResultElement', $taxable->getResult()->getTotal());
        $this->assertEquals($expectedTotalResult, $taxable->getResult()->getTotal());
        $this->assertEquals($expectedTaxes, $taxable->getResult()->getTaxes());
    }

    /**
     * @return array
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
                'expectedTotalResult' => ResultElement::create('24.1879', '19.99', '4.1979', '0.0021'),
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
                'expectedTotalResult' => ResultElement::create('71.2476', '65.97', '5.2776', '0.0024'),
                'expectedTaxes' => [TaxResultElement::create('1', '0.08', '65.97', '5.2776')],
            ],
            'mixed' => [
                'items' => [
                    [
                        Result::ROW => ResultElement::create('22.5887', '19.99', '2.5987', '0.0013'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.5992'),
                            TaxResultElement::create('2', '0.05', '19.99', '0.9995'),
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
                        Result::ROW => ResultElement::create('28.0683', '23.99', '4.0783', '0.0017'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '23.99', '1.9192'),
                            TaxResultElement::create('4', '0.09', '23.99', '2.1591'),
                        ],
                    ],
                ],
                'expectedTotalResult' => ResultElement::create('75.7256', '65.97', '9.7556', '0.0044'),
                'expectedTaxes' => [
                    TaxResultElement::create('1', '0.08', '65.97', '5.2776'),
                    TaxResultElement::create('2', '0.05', '19.99', '0.9995'),
                    TaxResultElement::create('3', '0.06', '21.99', '1.3194'),
                    TaxResultElement::create('4', '0.09', '23.99', '2.1591'),
                ],
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
        ];
    }
}
