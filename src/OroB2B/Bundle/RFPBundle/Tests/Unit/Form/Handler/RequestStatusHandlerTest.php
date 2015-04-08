<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Handler;

use OroB2B\Bundle\RFPBundle\Form\Handler\RequestStatusHandler;

class RequestStatusHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStatusHandler
     */
    protected $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->any())
            ->method('isValid')
            ->willReturn(true);

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $request->expects($this->any())
            ->method('getMethod')
            ->willReturn('POST');

        $om = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new RequestStatusHandler($form, $request, $om, $translator);
    }

    /**
     * Test process
     */
    public function testProcess()
    {
        $requestStatus = $this->getMockBuilder('OroB2B\Bundle\RFPBundle\Entity\RequestStatus')
            ->disableOriginalConstructor()
            ->getMock();

        $requestStatus->expects($this->once())
            ->method('setLocale');

        $requestStatus->expects($this->once())
            ->method('getId');

        $this->assertTrue($this->handler->process($requestStatus));
    }
}
