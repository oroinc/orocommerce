<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Filter;

use Symfony\Component\Form\Extension\Core\View\ChoiceView;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;

use OroB2B\Bundle\PricingBundle\Filter\ProductPriceFilter;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitLabelFormatter
     */
    protected $formatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListRequestHandler
     */
    protected $requestHandler;

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

        $this->formatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestHandler = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productPriceFilter = new ProductPriceFilter(
            $this->formFactory,
            $this->filterUtility,
            $this->formatter,
            $this->requestHandler
        );
    }

    public function tearDown()
    {
        unset(
            $this->formFactory,
            $this->form,
            $this->filterUtility,
            $this->productPriceFilter,
            $this->formatter,
            $this->requestHandler
        );
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
        $this->formatter->expects($this->once())
            ->method('format')
            ->with('test value', true)
            ->willReturn('formatted test label');

        $formView = $this->createFormView();
        $formView->vars['formatter_options'] = [];

        $typeFormView = $this->createFormView($formView);
        $typeFormView->vars['choices'] = [];

        $unitFormView = $this->createFormView($formView);
        $unitFormView->vars['choices'] = [new ChoiceView('test data', 'test value', 'test label')];

        $formView->children = ['type' => $typeFormView, 'unit' => $unitFormView];

        $this->form->expects($this->any())
            ->method('createView')
            ->willReturn($formView);

        $metadata = $this->productPriceFilter->getMetadata();

        $this->assertArrayHasKey('unitChoices', $metadata);
        $this->assertInternalType('array', $metadata['unitChoices']);
        $this->assertEquals(
            [
                [
                    'data' => 'test data',
                    'value' => 'test value',
                    'label' => 'test label',
                    'shortLabel' => 'formatted test label',
                ]
            ],
            $metadata['unitChoices']
        );
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
