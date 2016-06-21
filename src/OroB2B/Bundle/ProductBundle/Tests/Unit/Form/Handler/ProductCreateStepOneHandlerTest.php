<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\ProductBundle\Form\Handler\ProductCreateStepOneHandler;

class ProductCreateStepOneHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductCreateStepOneHandler
     */
    protected $handler;

    /**
     * @var FormInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $form;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;


    protected function setUp()
    {
        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');
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
            ->method('submit')
            ->with($this->request);
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
