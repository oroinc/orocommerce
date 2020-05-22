<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CMSBundle\Entity\EntityListener\PageEntityListener;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class PageEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var PageEntityListener */
    private $entityListener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->entityListener = new PageEntityListener($this->doctrineHelper, $this->messageProducer);
    }

    public function testPostRemove()
    {
        $result = [
            ['id' => 3],
            ['id' => 5],
        ];

        /** @var \PHPUnit\Framework\MockObject\MockObject $query */
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        /** @var \PHPUnit\Framework\MockObject\MockObject $qb */
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('expr')
            ->willReturn(new Query\Expr());
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        /** @var \PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityRepository::class);
        $em->expects($this->once())
            ->method('createQueryBuilder')
            ->with('content_node')
            ->willReturn($qb);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(ContentNode::class)
            ->willReturn($em);

        $this->messageProducer
            ->expects($this->at(0))
            ->method('send')
            ->with('oro.web_catalog.calculate_cache', ['webCatalogId' => 3]);

        $this->messageProducer
            ->expects($this->at(1))
            ->method('send')
            ->with('oro.web_catalog.calculate_cache', ['webCatalogId' => 5]);

        $entity = $this->getEntity(Page::class, ['id' => 2]);
        $lifecycleEventArgs = $this->createMock(LifecycleEventArgs::class);

        $this->entityListener->preRemove($entity, $lifecycleEventArgs);
        $this->entityListener->postRemove($entity, $lifecycleEventArgs);
    }

    public function testPostRemoveWithoutWebCatalog()
    {
        $result = [];

        /** @var \PHPUnit\Framework\MockObject\MockObject $query */
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        /** @var \PHPUnit\Framework\MockObject\MockObject $qb */
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('expr')
            ->willReturn(new Query\Expr());
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        /** @var \PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityRepository::class);
        $em->expects($this->once())
            ->method('createQueryBuilder')
            ->with('content_node')
            ->willReturn($qb);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(ContentNode::class)
            ->willReturn($em);

        $this->messageProducer
            ->expects($this->never())
            ->method('send');

        $entity = $this->getEntity(Page::class, ['id' => 2]);
        $lifecycleEventArgs = $this->createMock(LifecycleEventArgs::class);

        $this->entityListener->preRemove($entity, $lifecycleEventArgs);
        $this->entityListener->postRemove($entity, $lifecycleEventArgs);
    }
}
