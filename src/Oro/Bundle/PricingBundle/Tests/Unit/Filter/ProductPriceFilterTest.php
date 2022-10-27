<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\PricingBundle\Filter\ProductPriceFilter;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;

class ProductPriceFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formatter;

    /** @var PriceListRequestHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $requestHandler;

    /** @var ProductPriceFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formatter = $this->createMock(UnitLabelFormatterInterface::class);
        $this->requestHandler = $this->createMock(PriceListRequestHandler::class);
        $this->form = $this->createMock(FormInterface::class);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->form);

        $this->filter = new ProductPriceFilter(
            $formFactory,
            new FilterUtility(),
            $this->formatter,
            $this->requestHandler
        );
    }

    /**
     * @dataProvider parseDataDataProvider
     */
    public function testParseData(array $data, array|bool $expected)
    {
        $this->assertEquals($expected, ReflectionUtil::callMethod($this->filter, 'parseData', [$data]));
    }

    public function parseDataDataProvider(): array
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

        $formView = new FormView();
        $formView->vars['formatter_options'] = ['decimals' => 2];
        $formView->vars['array_separator'] = ',';
        $formView->vars['array_operators'] = [9, 10];
        $formView->vars['data_type'] = 'data_integer';

        $typeFormView = new FormView($formView);
        $typeFormView->vars['choices'] = [];

        $unitFormView = new FormView($formView);
        $unitFormView->vars['choices'] = [new ChoiceView('test data', 'test value', 'test label')];

        $formView->children = ['type' => $typeFormView, 'unit' => $unitFormView];

        $this->form->expects($this->any())
            ->method('createView')
            ->willReturn($formView);

        $metadata = $this->filter->getMetadata();

        $this->assertArrayHasKey('unitChoices', $metadata);
        $this->assertIsArray($metadata['unitChoices']);
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
}
