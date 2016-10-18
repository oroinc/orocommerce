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

class PriceListQueryConverterExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListQueryConverterExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new PriceListQueryConverterExtension();
    }

    public function testConvertWithPriceLists()
    {
        $expressionBuilder = $this->initializeExpressionBuilder();
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        $source = $this->getMockBuilder(AbstractQueryDesigner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $source->expects($this->once())
            ->method('getDefinition')
            ->willReturn(json_encode($definition));

        $tableAliasByColumn = $this->extension->convert($source, $qb);
        $expectedAliases = [
            'Oro\Bundle\PricingBundle\Entity\PriceList|42' => '_plt1'
        ];
        $this->assertEquals($expectedAliases, $tableAliasByColumn);
    }

    public function testConvertWithPrices()
    {
        $expressionBuilder = $this->initializeExpressionBuilder();
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        $source = $this->getMockBuilder(AbstractQueryDesigner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $source->expects($this->once())
            ->method('getDefinition')
            ->willReturn(json_encode($definition));

        $tableAliasByColumn = $this->extension->convert($source, $qb);
        $expectedAliases = [
            'Oro\Bundle\PricingBundle\Entity\PriceList|42' => '_plt1',
            'Oro\Bundle\PricingBundle\Entity\PriceList::prices|42' => '_plt2'
        ];
        $this->assertEquals($expectedAliases, $tableAliasByColumn);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function initializeExpressionBuilder()
    {
        $expressionBuilder = $this->getMockBuilder(ExpressionBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $expressionBuilder->expects($this->any())
            ->method('eq')
            ->willReturnCallback(
                function ($x, $y) {
                    return $x . ' = ' . $y;
                }
            );

        $expressionBuilder->expects($this->any())
            ->method('andX')
            ->willReturnCallback(
                function () {
                    $args = func_get_args();
                    return implode(' AND ', $args);
                }
            );

        return $expressionBuilder;
    }
}
