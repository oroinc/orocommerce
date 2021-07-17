<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\ProductBundle\Form\Handler\ProductCreateStepOneHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductCreateStepOneHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductCreateStepOneHandler
     */
    protected $handler;

    /**
     * @var FormInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $form;

    /**
     * @var Request|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $request;

    protected function setUp(): void
    {
        $this->form = $this->createMock('Symfony\Component\Form\FormInterface');
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new ProductCreateStepOneHandler($this->form, $this->request);
    }

    protected function assertValidForm($isValid = true)
    {
        $this->request->expects($this->once())
            ->method('isMethod')
            ->with('POST')
            ->will($this->returnValue(true));
        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isSubmitted')
            ->will($this->returnValue(true));
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue($isValid));
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
