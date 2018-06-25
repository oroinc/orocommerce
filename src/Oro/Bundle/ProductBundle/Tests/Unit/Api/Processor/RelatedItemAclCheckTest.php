<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ProductBundle\Api\Processor\RelatedItemAclCheck;
use Oro\Component\EntitySerializer\DoctrineHelper;

class RelatedItemAclCheckTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var RelatedItemAclCheck
     */
    private $relatedItemAclCheck;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getRootAlias')
            ->willReturn('p');

        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->relatedItemAclCheck = new RelatedItemAclCheck($this->doctrineHelper);
    }

    public function testProcess()
    {
        $qb = new QueryBuilder($this->entityManager);

        $qb->from('RelatedProduct', 'p');
        $context = $this->createContextWithQuery($qb);

        $this->relatedItemAclCheck->process($context);

        $this->assertEquals(
            'SELECT FROM RelatedProduct p LEFT JOIN p.product p LEFT JOIN p.relatedItem ri'
            . ' WHERE p.id IS NOT NULL AND ri.id IS NOT NULL',
            $qb->getDQL()
        );
    }

    /**
     * @param QueryBuilder $qb
     * @return Context|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createContextWithQuery(QueryBuilder $qb)
    {
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->any())
            ->method('getQuery')
            ->willReturn($qb);
        $context->expects($this->any())
            ->method('hasQuery')
            ->willReturn(true);

        return $context;
    }
}
