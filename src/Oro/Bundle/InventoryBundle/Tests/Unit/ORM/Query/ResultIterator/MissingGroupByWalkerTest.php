<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\ORM\Query\ResultIterator;

use Doctrine\ORM\Query\AST\GroupByClause;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Oro\Bundle\InventoryBundle\ORM\Query\ResultIterator\MissingGroupByWalker;

class MissingGroupByWalkerTest extends \PHPUnit\Framework\TestCase
{
    use SqlWalkerHelperTrait;

    /**
     * @var |\PHPUnit\Framework\MockObject\MockObject
     */
    protected $query;

    /**
     * @var |\PHPUnit\Framework\MockObject\MockObject
     */
    protected $parserResult;

    /**
     * @var |\PHPUnit\Framework\MockObject\MockObject
     */
    protected $queryComponents;

    /**
     * @var MissingGroupByWalker
     */
    protected $missingGroupByWalker;

    protected function setUp(): void
    {
        $this->missingGroupByWalker = new MissingGroupByWalker(
            $this->query,
            $this->parserResult,
            $this->getQueryComponents()
        );
    }

    public function testWalkSelectStatementAddsGroupBysIfNull()
    {
        /** @var SelectStatement * */
        $AST = $this->getDefaultAST();

        $this->missingGroupByWalker->walkSelectStatement($AST);
        $this->assertCount(count($this->getQueryComponents()), $AST->groupByClause->groupByItems);
    }

    public function testWalkStatementCompletesExistingGroupBy()
    {
        /** @var SelectStatement * */
        $AST = $this->getDefaultAST();
        $pathExpression1 = new PathExpression(
            PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            '_product',
            '_product'
        );
        $pathExpression2 = new PathExpression(
            PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            'test',
            'test'
        );
        $AST->groupByClause = new GroupByClause([$pathExpression1, $pathExpression2]);

        $this->missingGroupByWalker->walkSelectStatement($AST);
        $this->assertCount(count($this->getQueryComponents()) + 1, $AST->groupByClause->groupByItems);
    }
}
