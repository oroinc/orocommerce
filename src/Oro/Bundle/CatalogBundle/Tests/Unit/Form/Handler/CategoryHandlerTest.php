<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Form\Handler\CategoryHandler;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category as CategoryStub;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class CategoryHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var CategoryStub */
    private $entity;

    /** @var CategoryHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(ObjectManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->form = $this->createMock(Form::class);
        $this->entity = $this->createMock(CategoryStub::class);

        $this->handler = new CategoryHandler($this->manager, $this->eventDispatcher);
    }

    private function expectsAppendRemoveProducts(): void
    {
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new AfterFormProcessEvent($this->form, $this->entity), 'oro_catalog.category.edit');

        $appendProducts = $this->createMock(Form::class);
        $appendProducts->expects(self::once())
            ->method('getData')
            ->willReturn([new Product()]);

        $removeProducts = $this->createMock(Form::class);
        $removeProducts->expects(self::once())
            ->method('getData')
            ->willReturn([new Product()]);

        $this->form->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['appendProducts', $appendProducts],
                ['removeProducts', $removeProducts]
            ]);
    }

    private function expectsCategoryUnitPrecisionUpdate(): void
    {
        $defaultProductOptions = $this->createMock(CategoryDefaultProductOptions::class);
        $defaultProductOptions->expects(self::once())
            ->method('updateUnitPrecision');
        $this->entity->expects(self::any())
            ->method('getDefaultProductOptions')
            ->willReturn($defaultProductOptions);
    }

    public function testProcessUnsupportedRequest(): void
    {
        $request = new Request();
        $request->setMethod('GET');

        $this->form->expects(self::once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects(self::never())
            ->method('submit');

        $this->assertFalse($this->handler->process($this->entity, $this->form, $request));
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessSupportedRequest(string $method): void
    {
        $request = new Request();
        $request->initialize([], self::FORM_DATA);
        $request->setMethod($method);

        $this->form->expects(self::once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->expectsAppendRemoveProducts();
        $this->expectsCategoryUnitPrecisionUpdate();

        $this->form->expects(self::once())
            ->method('submit')
            ->with(self::FORM_DATA);

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->expects(self::once())
            ->method('findOneByProduct')
            ->willReturn(new CategoryStub());
        $this->manager->expects(self::once())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($categoryRepository);

        self::assertTrue($this->handler->process($this->entity, $this->form, $request));
    }

    public function supportedMethods(): array
    {
        return [['POST'], ['PUT']];
    }

    public function testProcessSupportedRequestWithInvalidData(): void
    {
        $request = new Request();
        $request->initialize([], self::FORM_DATA);
        $request->setMethod('POST');

        $this->form->expects(self::once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $this->form->expects(self::once())
            ->method('submit')
            ->with(self::FORM_DATA);

        $this->manager->expects(self::never())
            ->method('getRepository');

        self::assertFalse($this->handler->process($this->entity, $this->form, $request));
    }

    public function testProcessValidData(): void
    {
        $request = new Request();
        $request->initialize([], self::FORM_DATA);
        $request->setMethod('POST');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new AfterFormProcessEvent($this->form, $this->entity), 'oro_catalog.category.edit');

        $this->form->expects(self::once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects(self::once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->expectsAppendRemoveProducts();
        $this->expectsCategoryUnitPrecisionUpdate();

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->expects(self::once())
            ->method('findOneByProduct')
            ->willReturn(new CategoryStub());
        $this->manager->expects(self::once())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($categoryRepository);
        $this->manager->expects(self::once())
            ->method('persist');
        $this->manager->expects(self::once())
            ->method('flush');

        self::assertTrue($this->handler->process($this->entity, $this->form, $request));
    }
}
