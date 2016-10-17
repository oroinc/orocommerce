<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Resolver;

use Doctrine\Common\Collections\Expr\Comparison as DoctrineComparison;

use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Resolver\QueryPlaceholderResolver;

class QueryPlaceholderResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PlaceholderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $placeholder;

    /**
     * @var QueryPlaceholderResolver
     */
    private $placeholderResolver;

    protected function setUp()
    {
        $this->placeholder = $this
            ->getMockBuilder(PlaceholderInterface::class)
            ->getMock();

        $this->placeholderResolver = new QueryPlaceholderResolver($this->placeholder, []);
    }

    public function testReplaceInFrom()
    {
        $query = new Query();
        $fromArray = [
            'oro_first_TEST_ID',
            'oro_second',
            'oro_third_NAME_ID'
        ];
        $query->from($fromArray);

        $this->placeholder->expects($this->exactly(3))
            ->method('replaceDefault')
            ->withConsecutive(
                ['oro_first_TEST_ID'],
                ['oro_second'],
                ['oro_third_NAME_ID']
            )
            ->willReturnOnConsecutiveCalls('oro_first_1', 'oro_second', 'oro_third_NAME_ID');

        $this->placeholderResolver->replace($query);

        $this->assertEquals(
            [
                'oro_first_1',
                'oro_second',
                'oro_third_NAME_ID'
            ],
            $query->getFrom()
        );
    }

    public function testReplaceInSelect()
    {
        $query = new Query();
        $query->select([
            'text.oro_first_TEST_ID as test_id',
            'text.oro_second',
            'text.oro_third_NAME_ID'
        ]);

        $this->placeholder->expects($this->exactly(3))
            ->method('replaceDefault')
            ->withConsecutive(
                ['text.oro_first_TEST_ID'],
                ['text.oro_second'],
                ['text.oro_third_NAME_ID']
            )
            ->willReturnOnConsecutiveCalls('oro_first_1', 'oro_second', 'oro_third_NAME_ID');

        $this->placeholderResolver->replace($query);

        $this->assertEquals(
            [
                'text.oro_first_1',
                'text.oro_second',
                'text.oro_third_NAME_ID'
            ],
            $query->getSelect()
        );
    }

    public function testReplaceInCriteria()
    {
        $expr = new Comparison("field_name_NAME_ID", "=", "value");
        $criteria = new Criteria();
        $criteria->where($expr);
        $criteria->orderBy(['sorter_TEST_ID' => 'ASC']);

        $query = new Query();
        $query->setCriteria($criteria);

        $this->placeholder->expects($this->exactly(2))
            ->method('replaceDefault')
            ->willReturn('field_name_2');

        $this->placeholderResolver->replace($query);

        $expectedExpr = new Comparison("field_name_2", "=", "value");
        $expectedCriteria = new Criteria();
        $expectedCriteria->where($expectedExpr);
        $expectedCriteria->orderBy(['field_name_2' => 'ASC']);

        /** @var DoctrineComparison $expectedComparison */
        $expectedComparison = $expectedCriteria->getWhereExpression();
        /** @var DoctrineComparison $actualComparison */
        $actualComparison = $query->getCriteria()->getWhereExpression();

        $this->assertComparisonEquals($expectedComparison, $actualComparison);
        $this->assertEquals($expectedCriteria->getOrderings(), $query->getCriteria()->getOrderings());
    }

    /**
     * @param DoctrineComparison $expected
     * @param DoctrineComparison $actual
     */
    private function assertComparisonEquals(DoctrineComparison $expected, DoctrineComparison $actual)
    {
        $this->assertEquals($expected->getField(), $actual->getField());
        $this->assertEquals($expected->getOperator(), $actual->getOperator());
        $this->assertEquals($expected->getValue(), $actual->getValue());
    }
}
