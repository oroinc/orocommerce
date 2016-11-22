<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\QueryDesigner;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\OrmQueryConverterTest;
use Oro\Bundle\ShippingBundle\QueryDesigner\SelectQueryConverter;
use Oro\Bundle\ShippingBundle\QueryDesigner\ShippingProductQueryDesigner;

class SelectQueryConverterTest extends OrmQueryConverterTest
{
    public function testConvert()
    {
        $doctrine = $this->getDoctrine(
            [
                BusinessUnit::class => [],
                Product::class => [
                    'owner' => []
                ]
            ],
            [
                BusinessUnit::class => ['id'],
                Product::class => ['id'],
            ]
        );

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $doctrine->getManagerForClass(Product::class);
        $em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn(new QueryBuilder($em));

        $converter = new SelectQueryConverter(
            $this->getFunctionProvider(),
            $this->getVirtualFieldProvider(),
            $doctrine
        );

        $queryDesigner = new ShippingProductQueryDesigner();
        $queryDesigner->setEntity(Product::class);
        $queryDesigner->setDefinition(json_encode([
            'columns' => [
                [
                    'name' => 'id',
                    'label' => 'product_id',
                ],
                [
                    'name' => sprintf(
                        'owner+%s::id',
                        BusinessUnit::class
                    ),
                    'table_identifier' => sprintf(
                        '%s::owner',
                        Product::class
                    ),
                    'label' => 'relation_id',
                ]
            ]
        ]));

        $qb = $converter->convert($queryDesigner);

        $this->assertEquals([new From(Product::class, 't1')], $qb->getDQLPart('from'));
        $this->assertEquals(['t1' => [new Join(Join::INNER_JOIN, 't1.owner', 't2')]], $qb->getDQLPart('join'));
        $this->assertEquals([
            new Select(['t1.id as product_id']),
            new Select('t2.id as relation_id')
        ], $qb->getDQLPart('select'));
    }
}
