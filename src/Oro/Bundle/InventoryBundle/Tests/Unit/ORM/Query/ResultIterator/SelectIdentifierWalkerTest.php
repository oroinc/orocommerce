<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\ORM\Query\ResultIterator;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
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
use Oro\Bundle\InventoryBundle\ORM\Query\ResultIterator\SelectIdentifierWalker;

class SelectIdentifierWalkerTest extends \PHPUnit\Framework\TestCase
{
    /** @var SelectIdentifierWalker */
    private $selectIdentifierWalker;

    protected function setUp(): void
    {
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->createMock(Connection::class));

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $rootMetadata = new ClassMetadata('Entity\Root');
        $rootMetadata->setIdentifier(['e']);

        $this->selectIdentifierWalker = new SelectIdentifierWalker(
            $query,
            $this->createMock(ParserResult::class),
            ['e' => ['map' => null, 'metadata' => $rootMetadata]]
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

    public function testWalkSelectStatementShouldSimplyAddDefaultSelect(): void
    {
        $ast = $this->getAst();

        $this->selectIdentifierWalker->walkSelectStatement($ast);

        $this->assertEmpty($ast->groupByClause);
        $this->assertNull($ast->selectClause->selectExpressions[0]->fieldIdentificationVariable);
        $this->assertEquals('e', $ast->selectClause->selectExpressions[0]->expression->identificationVariable);
    }

    public function testWalkSelectStatementShouldAddMissingGroupBy(): void
    {
        $ast = $this->getAst();
        $pathExpression = new PathExpression(
            PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            'test1',
            'test1'
        );
        $ast->groupByClause = new GroupByClause([$pathExpression]);

        $this->selectIdentifierWalker->walkSelectStatement($ast);

        $this->assertCount(2, $ast->groupByClause->groupByItems);
        $addedGroupBy = $ast->groupByClause->groupByItems[1];
        $this->assertEquals('e', $addedGroupBy->field);
        $this->assertEquals('e', $addedGroupBy->identificationVariable);
    }
}
