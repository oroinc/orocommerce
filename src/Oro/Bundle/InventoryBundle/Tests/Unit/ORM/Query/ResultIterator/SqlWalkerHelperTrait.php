<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\ORM\Query\ResultIterator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\AST\FromClause;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectClause;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\ProductBundle\Entity\Product;

trait SqlWalkerHelperTrait
{
    /**
     * @return SelectStatement
     */
    protected function getDefaultAST()
    {
        $selectExpr = new SelectExpression('o', 'test', null);
        $selectClause = new SelectClause([$selectExpr], false);
        $rangeVarDeclaration = new RangeVariableDeclaration(Product::class, 'o');
        $from1 = new IdentificationVariableDeclaration($rangeVarDeclaration, null, []);
        $fromClause = new FromClause([$from1]);
        $AST = new SelectStatement($selectClause, $fromClause);

        return $AST;
    }

    /**
     * @return array
     */
    protected function getQueryComponents()
    {
        $rootMetadata = new ClassMetadata(InventoryLevel::class);
        $rootMetadata->setIdentifier(['o']);
        $otherMetadata = new ClassMetadata(InventoryLevel::class);
        $otherMetadata->setIdentifier(['i']);

        return [
            '_product' => [
                'metadata' => $otherMetadata,
            ],
            '_productUnitPrecision' => [
                'metadata' => $otherMetadata,
            ],
            '_warehouse' => [
                'metadata' => $otherMetadata,
            ],
            'o' => [
                'map' => null,
                'metadata' => $rootMetadata,
            ],
        ];
    }
}
