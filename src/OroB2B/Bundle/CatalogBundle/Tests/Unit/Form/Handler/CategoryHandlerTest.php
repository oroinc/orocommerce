<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Form\Handler;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Form\Handler\CategoryHandler;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class CategoryHandlerTest extends FormHandlerTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->entity = new Category();
        $this->handler = new CategoryHandler($this->form, $this->request, $this->manager);
    }

    /**
     * @dataProvider supportedMethods
     * @param string $method
     * @param boolean $isValid
     * @param boolean $isProcessed
     */
    public function testProcessSupportedRequest($method, $isValid, $isProcessed)
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->form->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue($isValid));

        if ($isValid) {
            $this->assertAppendRemoveProducts();
        }

        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->assertEquals($isProcessed, $this->handler->process($this->entity));
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

        $this->assertAppendRemoveProducts();

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->entity);

        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity));
    }

    protected function assertAppendRemoveProducts()
    {
        $appendProducts = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $appendProducts->expects($this->once())
            ->method('getData')
            ->will($this->returnValue([new Product()]));

        $this->form->expects($this->at(3))
            ->method('get')
            ->with('appendProducts')
            ->will($this->returnValue($appendProducts));

        $removeProducts = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $removeProducts->expects($this->once())
            ->method('getData')
            ->will($this->returnValue([new Product()]));

        $this->form->expects($this->at(4))
            ->method('get')
            ->with('removeProducts')
            ->will($this->returnValue($removeProducts));
    }
}
