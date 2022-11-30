<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\TabbedContentWidgetType;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\TabbedContentItem;
use Oro\Bundle\CMSBundle\EventListener\TabbedContentWidgetFormEventListener;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\Form;

class TabbedContentWidgetFormEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private EntityRepository|\PHPUnit\Framework\MockObject\MockObject $repository;

    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    private TabbedContentWidgetFormEventListener $listener;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with(TabbedContentItem::class)
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::any())
            ->method('getRepository')
            ->with(TabbedContentItem::class)
            ->willReturn($this->repository);

        $this->listener = new TabbedContentWidgetFormEventListener($managerRegistry);
    }

    public function testOnBeforeFlush(): void
    {
        $contentItem1 = $this->getEntity(TabbedContentItem::class, ['id' => 1001]);
        $contentItem2 = $this->getEntity(TabbedContentItem::class, ['id' => 2002]);

        $data = new ContentWidget();
        $data->setWidgetType(TabbedContentWidgetType::getName());
        $data->setSettings(['tabbedContentItems' => new ArrayCollection([$contentItem2]), 'param' => 'value']);

        $this->repository
            ->expects(self::once())
            ->method('findBy')
            ->with(['contentWidget' => $data])
            ->willReturn([$contentItem1]);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($contentItem2);

        $this->entityManager
            ->expects(self::once())
            ->method('remove')
            ->with($contentItem1);

        $this->listener->onBeforeFlush(new AfterFormProcessEvent($this->createMock(Form::class), $data));

        self::assertEquals(['param' => 'value'], $data->getSettings());
    }

    public function testOnBeforeFlushInvalidContentWidgetType(): void
    {
        $data = new ContentWidget();
        $data->setWidgetType('test');

        $this->repository
            ->expects(self::never())
            ->method(self::anything());

        $this->entityManager
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onBeforeFlush(new AfterFormProcessEvent($this->createMock(Form::class), $data));
    }

    public function testOnBeforeFlushInvalidData(): void
    {
        $data = new \stdClass();

        $this->repository
            ->expects(self::never())
            ->method(self::anything());

        $this->entityManager
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onBeforeFlush(new AfterFormProcessEvent($this->createMock(Form::class), $data));
    }
}
