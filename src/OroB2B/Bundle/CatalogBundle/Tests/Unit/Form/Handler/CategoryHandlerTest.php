<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Form\Handler;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Event\CategoryEditEvent;
use OroB2B\Bundle\CatalogBundle\Form\Handler\CategoryHandler;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CategoryHandlerTest extends FormHandlerTestCase
{
    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /**
     * @var Category
     */
    protected $entity;

    protected function setUp()
    {
        parent::setUp();

        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->entity = new Category();
        $this->handler = new CategoryHandler($this->form, $this->request, $this->manager, $this->eventDispatcher);
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
            $this->eventDispatcher
                ->expects($this->once())
                ->method('dispatch')
                ->with(CategoryEditEvent::NAME, $this->prepareCategoryEditEvent());
        } else {
            $this->eventDispatcher
                ->expects($this->never())
                ->method('dispatch');
        }

        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->mockProductCategory();

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

        $this->mockProductCategory();

        $this->manager->expects($this->any())
            ->method('persist');

        $this->manager->expects($this->any())
            ->method('flush');

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(CategoryEditEvent::NAME, $this->prepareCategoryEditEvent());

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

    protected function mockProductCategory()
    {
        $category = new Category();
        $categoryRepository = $this
            ->getMockBuilder('OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $categoryRepository->expects($this->any())
            ->method('findOneByProduct')
            ->will($this->returnValue($category));
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BCatalogBundle:Category')
            ->will($this->returnValue($categoryRepository));
    }

    /**
     * @return CategoryEditEvent
     */
    protected function prepareCategoryEditEvent()
    {
        return (new CategoryEditEvent())
            ->setCategory($this->entity)
            ->setForm($this->form)
            ->setRequest($this->request);
    }
}
