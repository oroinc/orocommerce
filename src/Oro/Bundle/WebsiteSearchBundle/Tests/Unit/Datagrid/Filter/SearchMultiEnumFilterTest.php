<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter\AbstractSearchEnumFilterTest;
use Oro\Bundle\WebsiteSearchBundle\Datagrid\Filter\SearchMultiEnumFilter;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;

class SearchMultiEnumFilterTest extends AbstractSearchEnumFilterTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = new SearchMultiEnumFilter($this->formFactory, new FilterUtility(), $this->dictionaryManager);
    }

    public function testApply()
    {
        $fieldName = 'field_' . EnumIdPlaceholder::NAME;
        $value = [
            'value1',
            'value2'
        ];

        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with(
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison('field_value1', Comparison::EXISTS, null),
                        new Comparison('field_value2', Comparison::EXISTS, null),
                    ]
                )
            );

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName]);

        $this->assertTrue(
            $this->filter->apply(
                $ds,
                [
                    'type' => null,
                    'value' => $value,
                ]
            )
        );
    }

    public function testPrepareData()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->filter->prepareData([]);
    }
}
