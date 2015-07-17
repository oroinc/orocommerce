<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Filter;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;

use OroB2B\Bundle\PricingBundle\Filter\ProductPriceFilter;

class ProductPriceFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected $form;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FilterUtility
     */
    protected $filterUtility;

    /**
     * @var ProductPriceFilter
     */
    protected $productPriceFilter;

    public function setUp()
    {
        $this->form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $this->formFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->form));

        $this->filterUtility = $this->getMockBuilder('Oro\Bundle\FilterBundle\Filter\FilterUtility')
            ->disableOriginalConstructor()
            ->getMock();
        $this->filterUtility->expects($this->any())
            ->method('getExcludeParams')
            ->willReturn([]);

        $this->productPriceFilter = new ProductPriceFilter($this->formFactory, $this->filterUtility);
    }

    public function tearDown()
    {
        unset($this->formFactory, $this->form, $this->filterUtility, $this->productPriceFilter);
    }

    /**
     * @dataProvider parseDataDataProvider
     * @param $data
     * @param $expected
     */
    public function testParseData($data, $expected)
    {
        $this->assertEquals($expected, $this->productPriceFilter->parseData($data));
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

    public function testGetMetadata()
    {
        $formView = $this->createFormView();
        $formView->vars['formatter_options'] = [];

        $childFormView = $this->createFormView($formView);
        $childFormView->vars['choices'] = [];

        $formView->children = [
            'type' => $childFormView,
            'unit' => clone $childFormView
        ];

        $this->form->expects($this->any())
            ->method('createView')
            ->willReturn($formView);

        $metadata = $this->productPriceFilter->getMetadata();

        $this->assertArrayHasKey('unitChoices', $metadata);
        $this->assertInternalType('array', $metadata['unitChoices']);
    }

    /**
     * @param null|FormView $parent
     * @return FormView
     */
    protected function createFormView(FormView $parent = null)
    {
        return new FormView($parent);
    }
}
