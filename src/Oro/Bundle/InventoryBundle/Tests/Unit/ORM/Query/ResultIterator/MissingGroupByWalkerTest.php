<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\ORM\Query\ResultIterator;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\AST\FromClause;
use Doctrine\ORM\Query\AST\GroupByClause;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectClause;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\ParserResult;
use Oro\Bundle\InventoryBundle\ORM\Query\ResultIterator\MissingGroupByWalker;

class MissingGroupByWalkerTest extends \PHPUnit\Framework\TestCase
{
    private MissingGroupByWalker $missingGroupByWalker;

    protected function setUp(): void
    {
        $this->missingGroupByWalker = new MissingGroupByWalker(
            $this->createMock(AbstractQuery::class),
            $this->createMock(ParserResult::class),
            $this->getQueryComponents()
        );
    }

    private function getAst(): SelectStatement
    {
        return new SelectStatement(
            new SelectClause([new SelectExpression('e', 'id', null)], false),
            new FromClause([
                new IdentificationVariableDeclaration(new RangeVariableDeclaration('Test', 'e'), null, [])
            ])
        );
    }

    private function getQueryComponents(): array
    {
        $rootMetadata = new ClassMetadata('Entity\Root');
        $rootMetadata->setIdentifier(['e']);
        $productMetadata = new ClassMetadata('Entity\Product');
        $productMetadata->setIdentifier(['p']);

        return [
            '_product' => ['metadata' => $productMetadata],
            'e'        => ['map' => null, 'metadata' => $rootMetadata],
        ];
    }

    public function testWalkSelectStatementAddsGroupBysIfNull(): void
    {
        $ast = $this->getAst();

        $this->missingGroupByWalker->walkSelectStatement($ast);

        $this->assertCount(1, $ast->groupByClause->groupByItems);
    }

    public function testWalkStatementCompletesExistingGroupBy(): void
    {
        $ast = $this->getAst();
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
        $ast->groupByClause = new GroupByClause([$pathExpression1, $pathExpression2]);

        $this->missingGroupByWalker->walkSelectStatement($ast);

        $this->assertCount(count($this->getQueryComponents()) + 1, $ast->groupByClause->groupByItems);
    }
}
