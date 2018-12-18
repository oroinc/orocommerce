<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\PricingBundle\Filter\ProductPriceFilter;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;

class ProductPriceFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FormInterface
     */
    protected $form;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FilterUtility
     */
    protected $filterUtility;

    /**
     * @var ProductPriceFilter
     */
    protected $productPriceFilter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|UnitLabelFormatterInterface
     */
    protected $formatter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PriceListRequestHandler
     */
    protected $requestHandler;

    public function setUp()
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->formFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->form));

        $this->filterUtility = $this->getMockBuilder(FilterUtility::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filterUtility->expects($this->any())
            ->method('getExcludeParams')
            ->willReturn([]);

        $this->formatter = $this->getMockBuilder(UnitLabelFormatterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestHandler = $this->getMockBuilder(PriceListRequestHandler::class)
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
     * @param array $data
     * @param array $expected
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
                'data' => ['value' => 10, 'value_end' => 20, 'type' => 'type', 'unit' => 'unit'],
                'expected' => ['value' => 10, 'value_end' => 20, 'type' => 'type', 'unit' => 'unit']
            ],
            'without unit' => [
                'data' => ['value' => 10, 'value_end' => 20, 'type' => 'type'],
                'expected' => false
            ],
            'negative prica value' => [
                'data' => ['value' => -10, 'value_end' => -20, 'type' => 'type', 'unit' => 'unit'],
                'expected' => ['value' => 10, 'value_end' => 20, 'type' => 'type', 'unit' => 'unit']
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
        $formView->vars['formatter_options'] = ['decimals' => 2];
        $formView->vars['array_separator'] = ',';
        $formView->vars['array_operators'] = [9, 10];
        $formView->vars['data_type'] = 'data_integer';

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
