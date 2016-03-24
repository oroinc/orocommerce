<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\OrderBundle\Form\Handler\OrderHandler;
use OroB2B\Bundle\OrderBundle\Entity\Order;

class OrderHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrderHandler */
    protected $handler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface */
    protected $form;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Request */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager */
    protected $manager;

    /** @var Order */
    protected $entity;

    protected function setUp()
    {
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = new Request();

        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity = new Order();

        $this->handler = new OrderHandler(
            $this->form,
            $this->request,
            $this->manager
        );
    }

    public function testProcessUnsupportedRequest()
    {
        $this->request->setMethod('GET');
        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse($this->handler->process($this->entity));
    }

    /**
     * @dataProvider supportedMethods
     * @param string $method
     * @param boolean $isValid
     * @param boolean $isProcessed
     */
    public function testProcessSupportedRequest($method, $isValid, $isProcessed)
    {
        $this->request->setMethod($method);
        $this->form->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue($isValid));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->assertEquals($isProcessed, $this->handler->process($this->entity));
    }

    public function testProcessValidData()
    {
        $this->request->setMethod('POST');
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));
        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->entity);
        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity));
    }

    /**
     * @return array
     */
    public function supportedMethods()
    {
        return [
            'post valid' => [
                'method' => 'POST',
                'isValid' => true,
                'isProcessed' => true
            ],
            'invalid' => [
                'method' => 'POST',
                'isValid' => false,
                'isProcessed' => false
            ],
        ];
    }
}
