<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Form\Handler;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Component\Testing\Unit\FormHandlerTestCase;
use Oro\Bundle\AccountBundle\Form\Handler\WebsiteScopedDataHandler;

class WebsiteScopedDataHandlerTest extends FormHandlerTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entity = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Product')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new WebsiteScopedDataHandler(
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
            ->method('isValid')
            ->will($this->returnValue($isValid));

        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('submit')
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
            ->method('submit')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with('orob2b_product.product.edit', new AfterFormProcessEvent($this->form, $this->entity));

        $this->assertTrue($this->handler->process($this->entity));
    }
}
