<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Form\Handler\VisibilityFormDataHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class VisibilityFormDataHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var Product */
    private $entity;

    /** @var VisibilityFormDataHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->form = $this->createMock(Form::class);
        $this->entity = $this->createMock(Product::class);

        $this->handler = new VisibilityFormDataHandler($this->eventDispatcher);
    }

    public function testProcessUnsupportedRequest(): void
    {
        $request = new Request();
        $request->setMethod('GET');

        $this->form->expects(self::once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects(self::never())
            ->method('submit');

        $this->assertFalse($this->handler->process($this->entity, $this->form, $request));
    }

    public function testProcessValidData(): void
    {
        $request = new Request();
        $request->setMethod('POST');

        $this->form->expects(self::once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects(self::once())
            ->method('handleRequest')
            ->with($request);
        $this->form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new AfterFormProcessEvent($this->form, $this->entity), 'oro_product.product.edit');

        self::assertTrue($this->handler->process($this->entity, $this->form, $request));
    }

    public function testProcessSupportedRequestWithInvalidData(): void
    {
        $request = new Request();
        $request->setMethod('POST');

        $this->form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);
        $this->form->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        self::assertFalse($this->handler->process($this->entity, $this->form, $request));
    }
}
