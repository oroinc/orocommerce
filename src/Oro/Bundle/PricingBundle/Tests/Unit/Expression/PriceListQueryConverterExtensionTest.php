<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Expression;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Expression\PriceListQueryConverterExtension;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;

class PriceListQueryConverterExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var PriceListQueryConverterExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new PriceListQueryConverterExtension();
    }

    public function testConvertWithPriceLists()
    {
        $expressionBuilder = $this->initializeExpressionBuilder();
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('getRootAliases')
            ->willReturn(['root']);

        $qb->expects($this->any())
            ->method('expr')
            ->willReturn($expressionBuilder);
        $qb->expects($this->exactly(2))
            ->method('leftJoin')
            ->withConsecutive(
                [
                    PriceListToProduct::class,
                    '_plt0',
                    Join::WITH,
                    '_plt0.priceList = :priceList42 AND _plt0.product = root'
                ],
                [
                    PriceList::class,
                    '_plt1',
                    Join::WITH,
                    '_plt0.priceList = _plt1'
                ]
            );

        $definition = [
            'price_lists' => [42]
        ];
        $source = $this->createMock(AbstractQueryDesigner::class);
        $source->expects($this->once())
            ->method('getDefinition')
            ->willReturn(QueryDefinitionUtil::encodeDefinition($definition));

        $tableAliasByColumn = $this->extension->convert($source, $qb);
        $expectedAliases = [
            'Oro\Bundle\PricingBundle\Entity\PriceList|42' => '_plt1'
        ];
        $this->assertEquals($expectedAliases, $tableAliasByColumn);
    }

    public function testConvertWithPrices()
    {
        $expressionBuilder = $this->initializeExpressionBuilder();
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('getRootAliases')
            ->willReturn(['root']);

        $qb->expects($this->any())
            ->method('expr')
            ->willReturn($expressionBuilder);
        $qb->expects($this->exactly(3))
            ->method('leftJoin')
            ->withConsecutive(
                [
                    PriceListToProduct::class,
                    '_plt0',
                    Join::WITH,
                    '_plt0.priceList = :priceList42 AND _plt0.product = root'
                ],
                [
                    PriceList::class,
                    '_plt1',
                    Join::WITH,
                    '_plt0.priceList = _plt1'
                ],
                [
                    ProductPrice::class,
                    '_plt2',
                    Join::WITH,
                    '_plt2.product = root AND _plt2.priceList = _plt1'
                ]
            );

        $definition = [
            'prices' => [42]
        ];
        $source = $this->createMock(AbstractQueryDesigner::class);
        $source->expects($this->once())
            ->method('getDefinition')
            ->willReturn(QueryDefinitionUtil::encodeDefinition($definition));

        $tableAliasByColumn = $this->extension->convert($source, $qb);
        $expectedAliases = [
            'Oro\Bundle\PricingBundle\Entity\PriceList|42' => '_plt1',
            'Oro\Bundle\PricingBundle\Entity\PriceList::prices|42' => '_plt2'
        ];
        $this->assertEquals($expectedAliases, $tableAliasByColumn);
    }

    private function initializeExpressionBuilder(): ExpressionBuilder
    {
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $expressionBuilder->expects($this->any())
            ->method('eq')
            ->willReturnCallback(function ($x, $y) {
                return $x . ' = ' . $y;
            });
        $expressionBuilder->expects($this->any())
            ->method('andX')
            ->willReturnCallback(function () {
                return implode(' AND ', func_get_args());
            });

        return $expressionBuilder;
    }
}
