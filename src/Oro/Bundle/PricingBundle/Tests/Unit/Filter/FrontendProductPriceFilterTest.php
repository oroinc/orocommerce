<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Expr\Comparison as CommonComparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\PricingBundle\Filter\FrontendProductPriceFilter;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchNumberRangeFilter;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Component\Exception\UnexpectedTypeException;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;

class FrontendProductPriceFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var UnitLabelFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $formatter;

    /** @var SearchNumberRangeFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->formFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->form);

        $this->formatter = $this->createMock(UnitLabelFormatter::class);

        $this->filter = new FrontendProductPriceFilter($this->formFactory, new FilterUtility(), $this->formatter);
        $this->filter->init('test-filter', [
            FilterUtility::DATA_NAME_KEY => 'field_name',
        ]);
    }

    public function testThrowsExceptionForWrongFilterDatasourceAdapter()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->filter->apply(
            $this->createMock(FilterDatasourceAdapterInterface::class),
            [
                'type' => NumberRangeFilterType::TYPE_BETWEEN,
                'value' => 123,
                'value_end' => 155
            ]
        );
    }

    public function testApplyBetween()
    {
        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);

        $ds->expects($this->exactly(2))
            ->method('addRestriction')
            ->withConsecutive(
                [
                    new CommonComparison('decimal.minimal_price.CPL_ID_CURRENCY_kg', Comparison::GTE, 100),
                    FilterUtility::CONDITION_AND,
                    false,
                ],
                [
                    new CommonComparison('decimal.minimal_price.CPL_ID_CURRENCY_kg', Comparison::LTE, 150),
                    FilterUtility::CONDITION_AND,
                    false,
                ]
            );

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => 'minimal_price.CPL_ID_CURRENCY_UNIT']);
        $this->assertTrue(
            $this->filter->apply(
                $ds,
                [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => 100,
                    'value_end' => 150,
                    'unit' => 'kg',
                ]
            )
        );
    }

    public function testApplyNotBetween()
    {
        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with(
                new CompositeExpression(
                    FilterUtility::CONDITION_OR,
                    [
                        new CommonComparison(
                            'decimal.minimal_price.CPL_ID_CURRENCY_kg',
                            Comparison::LTE,
                            new Value(100)
                        ),
                        new CommonComparison(
                            'decimal.minimal_price.CPL_ID_CURRENCY_kg',
                            Comparison::GTE,
                            new Value(150)
                        ),
                    ]
                )
            );

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => 'minimal_price.CPL_ID_CURRENCY_UNIT']);
        $this->assertTrue(
            $this->filter->apply(
                $ds,
                [
                    'type' => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                    'value' => 100,
                    'value_end' => 150,
                    'unit' => 'kg',
                ]
            )
        );
    }

    /**
     * @param string $filterType
     * @param string $comparisonOperator
     * @param string $unit
     * @dataProvider applyDataProvider
     */
    public function testApply($filterType, $comparisonOperator, $unit)
    {
        $fieldValue = 100;

        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);

        $restriction = new CommonComparison(
            'decimal.minimal_price.CPL_ID_CURRENCY_' . $unit,
            $comparisonOperator,
            $fieldValue
        );
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with($restriction, FilterUtility::CONDITION_AND);

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => 'minimal_price.CPL_ID_CURRENCY_UNIT']);
        $this->assertTrue(
            $this->filter->apply(
                $ds,
                [
                    'type' => $filterType,
                    'value' => $fieldValue,
                    'value_end' => null,
                    'unit' => $unit,
                ]
            )
        );
    }

    public function applyDataProvider(): array
    {
        return [
            '>=' => [
                'filterType' => NumberFilterType::TYPE_GREATER_EQUAL,
                'comparisonOperator' => Comparison::GTE,
                'unit' => 'item',
            ],
            '>' => [
                'filterType' => NumberFilterType::TYPE_GREATER_THAN,
                'comparisonOperator' => Comparison::GT,
                'unit' => 'set',
            ],
            '=' => [
                'filterType' => NumberFilterType::TYPE_EQUAL,
                'comparisonOperator' => Comparison::EQ,
                'unit' => 'kg',
            ],
            '!=' => [
                'filterType' => NumberFilterType::TYPE_NOT_EQUAL,
                'comparisonOperator' => Comparison::NEQ,
                'unit' => 'piece',
            ],
            '<=' => [
                'filterType' => NumberFilterType::TYPE_LESS_EQUAL,
                'comparisonOperator' => Comparison::LTE,
                'unit' => 'liter',
            ],
            '<' => [
                'filterType' => NumberFilterType::TYPE_LESS_THAN,
                'comparisonOperator' => Comparison::LT,
                'unit' => 'box',
            ],
        ];
    }

    public function testGetMetadata()
    {
        $this->formatter->expects($this->once())
            ->method('format')
            ->with('test value', true)
            ->willReturn('formatted test label');

        $formView = new FormView();
        $formView->vars['formatter_options'] = ['decimals' => 0];
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

        $expected = [
            'name' => 'test-filter',
            'label' => 'Test-filter',
            'choices' => [],
            'lazy' => false,
            'formatterOptions' => [
                'decimals' => 0,
            ],
            'arraySeparator' => ',',
            'arrayOperators' => [9, 10],
            'dataType' => 'data_integer',
            'unitChoices' => [
                [
                    'data' => 'test data',
                    'value' => 'test value',
                    'label' => 'test label',
                    'shortLabel' => 'formatted test label',
                ]
            ],
        ];
        $this->assertEquals($expected, $this->filter->getMetadata());
    }
}
