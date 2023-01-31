<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CMSBundle\Entity\EntityListener\PageEntityListener;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\ReflectionUtil;

class PageEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    private DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private PageEntityListener $entityListener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->entityListener = new PageEntityListener($this->doctrineHelper, $this->messageProducer);
    }

    private function getPageEntity(int $id): Page
    {
        $page = new Page();
        ReflectionUtil::setId($page, $id);

        return $page;
    }

    public function testPostRemove(): void
    {
        $result = [
            ['id' => 3],
            ['id' => 5],
        ];

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($result);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::once())
            ->method('expr')
            ->willReturn(new Query\Expr());
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $em = $this->createMock(EntityRepository::class);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->with('content_node')
            ->willReturn($qb);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ContentNode::class)
            ->willReturn($em);

        $this->messageProducer->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [WebCatalogCalculateCacheTopic::getName(), [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => 3]],
                [WebCatalogCalculateCacheTopic::getName(), [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => 5]]
            );

        $entity = $this->getPageEntity(2);
        $lifecycleEventArgs = $this->createMock(LifecycleEventArgs::class);

        $this->entityListener->preRemove($entity, $lifecycleEventArgs);
        $this->entityListener->postRemove($entity, $lifecycleEventArgs);
    }

    public function testPostRemoveWithoutWebCatalog(): void
    {
        $result = [];

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($result);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::once())
            ->method('expr')
            ->willReturn(new Query\Expr());
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $em = $this->createMock(EntityRepository::class);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->with('content_node')
            ->willReturn($qb);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ContentNode::class)
            ->willReturn($em);

        $this->messageProducer->expects(self::never())
            ->method('send');

        $entity = $this->getPageEntity(2);
        $lifecycleEventArgs = $this->createMock(LifecycleEventArgs::class);

        $this->entityListener->preRemove($entity, $lifecycleEventArgs);
        $this->entityListener->postRemove($entity, $lifecycleEventArgs);
    }
}
