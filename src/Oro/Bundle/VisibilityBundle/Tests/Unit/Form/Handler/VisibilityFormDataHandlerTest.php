<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\VisibilityBundle\Form\Handler\VisibilityFormDataHandler;
use Oro\Component\Testing\Unit\FormHandlerTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class VisibilityFormDataHandlerTest extends FormHandlerTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entity = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Product')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new VisibilityFormDataHandler(
            $this->form,
            $this->request,
            $this->eventDispatcher
        );
    }

    /**
     * {@inheritdoc}
     * @dataProvider supportedMethods
     */
    public function testProcessSupportedRequest($method, $isValid, $isProcessed)
    {
        $this->form->expects($this->any())
            ->method('isSubmitted')
            ->will($this->returnValue(true));
        $this->form->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue($isValid));

        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);

        $this->assertEquals($isProcessed, $this->handler->process($this->entity));
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

    public function testProcessValidData()
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);
        $this->form->expects($this->any())
            ->method('isSubmitted')
            ->will($this->returnValue(true));
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new AfterFormProcessEvent($this->form, $this->entity), 'oro_product.product.edit');

        $this->assertTrue($this->handler->process($this->entity));
    }
}
