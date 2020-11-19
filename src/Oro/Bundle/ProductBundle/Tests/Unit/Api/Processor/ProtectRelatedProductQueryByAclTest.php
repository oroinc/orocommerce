<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ProductBundle\Api\Processor\ProtectRelatedProductQueryByAcl;
use Oro\Component\EntitySerializer\DoctrineHelper;

class ProtectRelatedProductQueryByAclTest extends GetListProcessorTestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ProtectRelatedProductQueryByAcl */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new ProtectRelatedProductQueryByAcl($this->doctrineHelper);
    }

    public function testProcess()
    {
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));
        $qb->from('RelatedProduct', 'p');

        $this->doctrineHelper->expects(self::once())
            ->method('getRootAlias')
            ->willReturn('p');

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        self::assertEquals(
            'SELECT FROM RelatedProduct p LEFT JOIN p.product p LEFT JOIN p.relatedItem ri'
            . ' WHERE p.id IS NOT NULL AND ri.id IS NOT NULL',
            $qb->getDQL()
        );
    }
}
