<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\ORM\Query\ResultIterator;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\AST\GroupByClause;
use Doctrine\ORM\Query\AST\PathExpression;
use Oro\Bundle\InventoryBundle\ORM\Query\ResultIterator\SelectIdentifierWalker;

class SelectIdentifierWalkerTest extends \PHPUnit\Framework\TestCase
{
    use SqlWalkerHelperTrait;

    /**
     * @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $query;

    /**
     * @var |\PHPUnit\Framework\MockObject\MockObject
     */
    protected $parserResult;

    /**
     * @var |\PHPUnit\Framework\MockObject\MockObject
     */
    protected $queryComponents = [];

    /**
     * @var SelectIdentifierWalker
     */
    protected $selectIdentifierWalker;

    /**
     * @var Connection|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $connection;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $em;

    protected function setUp(): void
    {
        $this->query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->query->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);

        $this->selectIdentifierWalker = new SelectIdentifierWalker(
            $this->query,
            $this->parserResult,
            $this->getQueryComponents()
        );
    }

    public function testWalkSelectStatementShouldSimplyAddDefaultSelect()
    {
        $AST = $this->getDefaultAST();
        $this->selectIdentifierWalker->walkSelectStatement($AST);
        $this->assertEmpty($AST->groupByClause);
        $this->assertNull($AST->selectClause->selectExpressions[0]->fieldIdentificationVariable);
        $this->assertEquals('o', $AST->selectClause->selectExpressions[0]->expression->identificationVariable);
    }

    public function testWalkSelectStatementShouldAddMissingGroupBy()
    {
        $AST = $this->getDefaultAST();
        $pathExpression = new PathExpression(
            PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            'test1',
            'test1'
        );
        $AST->groupByClause = new GroupByClause([$pathExpression]);
        $this->selectIdentifierWalker->walkSelectStatement($AST);
        $this->assertCount(2, $AST->groupByClause->groupByItems);
        $addedGroupBy = $AST->groupByClause->groupByItems[1];
        $this->assertEquals('o', $addedGroupBy->field);
        $this->assertEquals('o', $addedGroupBy->identificationVariable);
    }
}
