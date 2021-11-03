<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Form\Handler\ContentNodeHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ContentNodeHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ContentNodeHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->handler = new ContentNodeHandler($this->eventDispatcher, $this->doctrineHelper);
    }

    public function testProcessWhenNoPostPutRequest()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->never())
            ->method('isValid');
        $request = new Request();
        $request->setMethod('GET');
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');

        $result = $this->handler->process(new \stdClass, $form, $request);
        self::assertFalse($result);
    }

    public function testProcessWhenFormIsNotValid()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $request = new Request();
        $request->setMethod('POST');
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');

        $result = $this->handler->process(new \stdClass, $form, $request);
        self::assertFalse($result);
    }

    public function testProcessWhenThrowsException()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $request = new Request();
        $request->setMethod('POST');
        $data = new \stdClass;
        $manager = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($data)
            ->willReturn($manager);

        $manager->expects($this->once())
            ->method('beginTransaction');
        $manager->expects($this->once())
            ->method('persist')
            ->willThrowException(new \Exception());

        $this->expectException(\Exception::class);
        $manager->expects($this->once())
            ->method('rollback');
        $manager->expects($this->never())
            ->method('commit');

        $this->handler->process($data, $form, $request);
    }

    public function testProcess()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $request = new Request();
        $request->setMethod('POST');
        $data = new \stdClass;
        $manager = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($data)
            ->willReturn($manager);

        $manager->expects($this->once())
            ->method('beginTransaction');
        $manager->expects($this->once())
            ->method('persist')
            ->with($data);
        $manager->expects($this->once())
            ->method('flush');

        $manager->expects($this->never())
            ->method('rollback');
        $manager->expects($this->once())
            ->method('commit');

        $result = $this->handler->process($data, $form, $request);
        self::assertTrue($result);
    }
}
