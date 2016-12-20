<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\PricingBundle\Filter\FrontendProductPriceFilter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchNumberRangeFilter;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Doctrine\Common\Collections\Expr\Comparison as BaseComparison;
use Symfony\Component\Form\FormFactoryInterface;

class FrontendProductPriceFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SearchNumberRangeFilter
     */
    private $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        /* @var $formFactory FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
        $formFactory = $this->getMock(FormFactoryInterface::class);

        /* @var $filterUtility FilterUtility|\PHPUnit_Framework_MockObject_MockObject */
        $filterUtility = $this->getMock(FilterUtility::class);

        $this->filter = new FrontendProductPriceFilter($formFactory, $filterUtility);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid filter datasource adapter provided
     */
    public function testThrowsExceptionForWrongFilterDatasourceAdapter()
    {
        $ds = $this->getMock(FilterDatasourceAdapterInterface::class);
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

        $ds->expects($this->exactly(2))
            ->method('addRestriction')
            ->withConsecutive(
                [
                    new BaseComparison("decimal.minimal_price_CPL_ID_CURRENCY_kg", Comparison::LTE, 100),
                    FilterUtility::CONDITION_AND,
                    false,
                ],
                [
                    new BaseComparison("decimal.minimal_price_CPL_ID_CURRENCY_kg", Comparison::GTE, 150),
                    FilterUtility::CONDITION_AND,
                    false,
                ]
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
}
