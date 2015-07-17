<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Filter;

use OroB2B\Bundle\PricingBundle\Filter\ProductPriceFilter;

class ProductPriceFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ProductPriceFilter */
    protected $priceFilter;

    /** @var \Symfony\Component\Form\FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var \Oro\Bundle\FilterBundle\Filter\FilterUtility|\PHPUnit_Framework_MockObject_MockObject */
    protected $filterUtility;

    public function setUp()
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $this->formFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($form));

        $this->filterUtility = $this->getMockBuilder('Oro\Bundle\FilterBundle\Filter\FilterUtility')
            ->disableOriginalConstructor()
            ->getMock();
        $this->filterUtility->expects($this->any())
            ->method('getExcludeParams')
            ->will($this->returnValue([]));

        $this->priceFilter = new ProductPriceFilter($this->formFactory, $this->filterUtility);
    }

    /**
     * @dataProvider parseDataDataProvider
     * @param $data
     * @param $expected
     */
    public function testParseData($data, $expected)
    {
        $this->assertEquals($expected, $this->priceFilter->parseData($data));
    }

    /**
     * @return array
     */
    public function parseDataDataProvider()
    {
        return [
            'correct' => [
                'data' => ['value' => 20, 'type' => 'type'],
                'expected' => ['value' => 20, 'type' => 'type']
            ],
            'without value' => [
                'data' => [],
                'expected' => false
            ],
            'not numeric value' => [
                'data' => ['value' => 'not numeric'],
                'expected' => false
            ]
        ];
    }
}
