<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CatalogBundle\EventListener\SortOrderDialogTargetFormHandlerEventListener;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\CategoryStub;
use Oro\Bundle\CatalogBundle\Utils\SortOrderDialogTargetStorage;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\UIBundle\Route\Router as UiRouter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class SortOrderDialogTargetFormHandlerEventListenerTest extends TestCase
{
    private SortOrderDialogTargetStorage|MockObject $sortOrderDialogTargetStorage;

    private UiRouter|MockObject $uiRouter;

    private PropertyAccessorInterface|MockObject $propertyAccessor;

    private SortOrderDialogTargetFormHandlerEventListener $listener;

    public function setUp(): void
    {
        $this->sortOrderDialogTargetStorage = $this->createMock(SortOrderDialogTargetStorage::class);
        $this->uiRouter = $this->createMock(UiRouter::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);

        $this->listener = new SortOrderDialogTargetFormHandlerEventListener(
            $this->sortOrderDialogTargetStorage,
            $this->uiRouter,
            $this->propertyAccessor
        );
    }

    public function testOnFormAfterFlushWhenInvalidInputActionData(): void
    {
        $this->sortOrderDialogTargetStorage
            ->expects(self::never())
            ->method(self::anything());

        $this->uiRouter
            ->expects(self::once())
            ->method('getInputActionData')
            ->willThrowException(new \InvalidArgumentException());

        $this->listener->onFormAfterFlush($this->createMock(AfterFormProcessEvent::class));
    }

    public function testOnFormAfterFlushWhenHasInputActionDataWithoutTarget(): void
    {
        $this->uiRouter
            ->expects(self::once())
            ->method('getInputActionData')
            ->willReturn(['sample_key' => 'sample_value']);

        $this->sortOrderDialogTargetStorage
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onFormAfterFlush($this->createMock(AfterFormProcessEvent::class));
    }

    public function testOnFormAfterFlushWhenHasInputActionDataWithoutTargetEntity(): void
    {
        $targetName = 'sample_form_name';
        $this->uiRouter
            ->expects(self::once())
            ->method('getInputActionData')
            ->willReturn(
                [SortOrderDialogTargetFormHandlerEventListener::SORT_ORDER_DIALOG_TARGET => $targetName]
            );

        $form = $this->createMock(FormInterface::class);
        $form
            ->method('getName')
            ->willReturn($targetName);
        $form
            ->method('getData')
            ->willReturn(null);

        $this->propertyAccessor
            ->expects(self::once())
            ->method('getValue')
            ->with((object)[$targetName => $form])
            ->willReturn($form);

        $this->sortOrderDialogTargetStorage
            ->expects(self::never())
            ->method('addTarget');

        $event = new AfterFormProcessEvent($form, []);
        $this->listener->onFormAfterFlush($event);
    }

    public function testOnFormAfterFlushWhenHasInputActionDataWithTargetEntity(): void
    {
        $targetName = 'sample_form_name';
        $this->uiRouter
            ->expects(self::once())
            ->method('getInputActionData')
            ->willReturn(
                [SortOrderDialogTargetFormHandlerEventListener::SORT_ORDER_DIALOG_TARGET => $targetName]
            );

        $form = $this->createMock(FormInterface::class);
        $form
            ->method('getName')
            ->willReturn($targetName);
        $entity = new CategoryStub(42);
        $form
            ->method('getData')
            ->willReturn($entity);

        $this->propertyAccessor
            ->expects(self::once())
            ->method('getValue')
            ->with((object)[$targetName => $form])
            ->willReturn($form);

        $this->sortOrderDialogTargetStorage
            ->expects(self::once())
            ->method('addTarget')
            ->with(ClassUtils::getClass($entity), $entity->getId())
            ->willReturn(true);

        $event = new AfterFormProcessEvent($form, []);
        $this->listener->onFormAfterFlush($event);
    }
}
