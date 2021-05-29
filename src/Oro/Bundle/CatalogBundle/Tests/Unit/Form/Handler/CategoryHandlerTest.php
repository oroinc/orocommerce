<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Form\Handler\CategoryHandler;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\FormHandlerTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;

class CategoryHandlerTest extends FormHandlerTestCase
{
    /** @var Category */
    protected $entity;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->entity = $this->createMock(Category::class);
        $this->handler = new CategoryHandler($this->form, $this->request, $this->manager, $this->eventDispatcher);
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessSupportedRequest($method, $isValid, $isProcessed)
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->form->expects($this->any())
            ->method('isValid')
            ->willReturn($isValid);

        if ($isValid) {
            $this->assertAppendRemoveProducts();
            $this->assertCategoryUnitPrecisionUpdate();
        }

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);

        $this->mockProductCategory();

        $this->assertEquals($isProcessed, $this->handler->process($this->entity));
    }

    public function testProcessValidData()
    {
        $event = new AfterFormProcessEvent($this->form, $this->entity);
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($event, 'oro_catalog.category.edit');

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->assertAppendRemoveProducts();
        $this->assertCategoryUnitPrecisionUpdate();

        $this->mockProductCategory();

        $this->manager->expects($this->any())
            ->method('persist');

        $this->manager->expects($this->any())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity));
    }

    private function assertAppendRemoveProducts()
    {
        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(
            new AfterFormProcessEvent($this->form, $this->entity),
            'oro_catalog.category.edit'
        );

        $appendProducts = $this->createMock(Form::class);
        $appendProducts->expects($this->once())
            ->method('getData')
            ->willReturn([new Product()]);

        $removeProducts = $this->createMock(Form::class);
        $removeProducts->expects($this->once())
            ->method('getData')
            ->willReturn([new Product()]);

        $this->form->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['appendProducts', $appendProducts],
                ['removeProducts', $removeProducts]
            ]);
    }

    private function mockProductCategory()
    {
        $category = new Category();
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->expects($this->any())
            ->method('findOneByProduct')
            ->willReturn($category);
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with('OroCatalogBundle:Category')
            ->willReturn($categoryRepository);
    }

    private function assertCategoryUnitPrecisionUpdate()
    {
        $defaultProductOptions = $this->createMock(CategoryDefaultProductOptions::class);
        $defaultProductOptions->expects($this->once())
            ->method('updateUnitPrecision');
        $this->entity->expects($this->any())
            ->method('getDefaultProductOptions')
            ->willReturn($defaultProductOptions);
    }
}
