<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\ProductBundle\Form\Handler\ProductCreateStepOneHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductCreateStepOneHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    /** @var ProductCreateStepOneHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->request = $this->createMock(Request::class);

        $this->handler = new ProductCreateStepOneHandler($this->form, $this->request);
    }

    private function assertValidForm(bool $isValid): void
    {
        $this->request->expects($this->once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn(true);
        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn($isValid);
    }

    public function testFalseProcess()
    {
        $this->assertValidForm(false);
        $this->assertFalse($this->handler->process());
    }

    public function testTrueProcess()
    {
        $this->assertValidForm(true);
        $this->assertTrue($this->handler->process());
    }
}
