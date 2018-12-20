<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Common\Collections\Expr\Comparison as BaseComparison;
use Doctrine\Common\Collections\Expr\Comparison as CommonComparision;
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
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;

class FrontendProductPriceFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    private $filterName = 'filter-name';

    /**
     * @var string
     */
    private $dataName = 'field-name';

    /**
     * @var FormInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $form;

    /**
     * @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $formFactory;

    /**
     * @var FilterUtility|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filterUtility;

    /**
     * @var UnitLabelFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $formatter;

    /**
     * @var SearchNumberRangeFilter
     */
    private $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->formFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->form));

        $this->filterUtility = $this->createMock(FilterUtility::class);
        $this->filterUtility->expects($this->any())
            ->method('getExcludeParams')
            ->willReturn([]);

        $this->formatter = $this->createMock(UnitLabelFormatter::class);

        $this->filter = new FrontendProductPriceFilter($this->formFactory, $this->filterUtility);
        $this->filter->setFormatter($this->formatter);
        $this->filter->init($this->filterName, [
            FilterUtility::DATA_NAME_KEY => $this->dataName,
        ]);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid filter datasource adapter provided
     */
    public function testThrowsExceptionForWrongFilterDatasourceAdapter()
    {
        $ds = $this->createMock(FilterDatasourceAdapterInterface::class);
        $this->filter->apply(
            $ds,
            [
                'type' => NumberRangeFilterType::TYPE_BETWEEN,
                'value' => 123,
                'value_end' => 155,
            ]
        );
    }

    public function testApplyBetween()
    {
        $ds = $this->getMockBuilder(SearchFilterDatasourceAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ds->expects($this->exactly(2))
            ->method('addRestriction')
            ->withConsecutive(
                [
                    new BaseComparison("decimal.minimal_price_CPL_ID_CURRENCY_kg", Comparison::GTE, 100),
                    FilterUtility::CONDITION_AND,
                    false,
                ],
                [
                    new BaseComparison("decimal.minimal_price_CPL_ID_CURRENCY_kg", Comparison::LTE, 150),
                    FilterUtility::CONDITION_AND,
                    false,
                ]
            );

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => 'minimal_price_CPL_ID_CURRENCY_UNIT']);
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
        $ds = $this->getMockBuilder(SearchFilterDatasourceAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ds->expects($this->exactly(1))
            ->method('addRestriction')
            ->with(
                new CompositeExpression(
                    FilterUtility::CONDITION_OR,
                    [
                        new CommonComparision(
                            'decimal.minimal_price_CPL_ID_CURRENCY_kg',
                            Comparison::LTE,
                            new Value(100)
                        ),
                        new CommonComparision(
                            'decimal.minimal_price_CPL_ID_CURRENCY_kg',
                            Comparison::GTE,
                            new Value(150)
                        ),
                    ]
                )
            );

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => 'minimal_price_CPL_ID_CURRENCY_UNIT']);
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

        $ds = $this->getMockBuilder(SearchFilterDatasourceAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $restriction = new BaseComparison(
            "decimal.minimal_price_CPL_ID_CURRENCY_".$unit,
            $comparisonOperator,
            $fieldValue
        );
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with($restriction, FilterUtility::CONDITION_AND);

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => 'minimal_price_CPL_ID_CURRENCY_UNIT']);
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

    /**
     * @return array
     */
    public function applyDataProvider()
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

        $formView = $this->createFormView();
        $formView->vars['formatter_options'] = ['decimals' => 0];
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

        $expected = [
            'name' => 'filter-name',
            'label' => 'Filter-name',
            'choices' => [],
            'data_name' => 'field-name',
            'options' => [],
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

    /**
     * @param null|FormView $parent
     *
     * @return FormView
     */
    private function createFormView(?FormView $parent = null)
    {
        return new FormView($parent);
    }
}
