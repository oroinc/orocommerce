<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\VirtualFields\QueryDesigner;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsSelectQueryConverter;
use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\OrmQueryConverterTestCase;

class VirtualFieldsSelectQueryConverterTest extends OrmQueryConverterTestCase
{
    public function testConvert()
    {
        $doctrineHelper = $this->getDoctrineHelper(
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

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $doctrineHelper->getEntityManagerForClass(Product::class);
        $em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn(new QueryBuilder($em));

        $converter = new VirtualFieldsSelectQueryConverter(
            $this->getFunctionProvider(),
            $this->getVirtualFieldProvider(),
            $this->getVirtualRelationProvider(),
            $doctrineHelper
        );

        $queryDesigner = new QueryDesigner(
            Product::class,
            QueryDefinitionUtil::encodeDefinition([
                'columns' => [
                    [
                        'name' => 'id',
                        'label' => 'product_id'
                    ],
                    [
                        'name' => sprintf('owner+%s::id', BusinessUnit::class),
                        'table_identifier' => sprintf('%s::owner', Product::class),
                        'label' => 'relation_id'
                    ]
                ]
            ])
        );

        $qb = $converter->convert($queryDesigner);

        $this->assertEquals([new From(Product::class, 't1')], $qb->getDQLPart('from'));
        $this->assertEquals(['t1' => [new Join(Join::INNER_JOIN, 't1.owner', 't2')]], $qb->getDQLPart('join'));
        $this->assertEquals([
            new Select(['t1.id as product_id']),
            new Select('t2.id as relation_id')
        ], $qb->getDQLPart('select'));
    }
}
