<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Form\Handler\CategoryHandler;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category as CategoryStub;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Component\Testing\Unit\FormHandlerTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class CategoryHandlerTest extends FormHandlerTestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->entity = $this->createMock(CategoryStub::class);

        $this->handler = new CategoryHandler($this->form, $this->request, $this->manager, $this->eventDispatcher);
    }

    private function expectsAppendRemoveSortProducts(): void
    {
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new AfterFormProcessEvent($this->form, $this->entity), 'oro_catalog.category.edit');

        $appendProducts = $this->createMock(Form::class);
        $appendProducts->expects(self::once())
            ->method('getData')
            ->willReturn([new ProductStub()]);

        $removeProducts = $this->createMock(Form::class);
        $removeProducts->expects(self::once())
            ->method('getData')
            ->willReturn([new ProductStub()]);

        $sortOrder = $this->createMock(Form::class);
        $sortOrder->expects(self::once())
            ->method('getData')
            ->willReturn(new ArrayCollection([1 => ['data' => ['categorySortOrder' => [1]]]]));

        $this->form->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['appendProducts', $appendProducts],
                ['removeProducts', $removeProducts],
                ['sortOrder', $sortOrder]
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
    public function testProcessSupportedRequest(string $method, bool $isValid, bool $isProcessed): void
    {
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects(self::once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn($isValid);

        $this->expectsAppendRemoveSortProducts();
        $this->expectsCategoryUnitPrecisionUpdate();

        $this->form->expects(self::once())
            ->method('submit')
            ->with(self::FORM_DATA);

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $productRepository = $this->createMock(ProductRepository::class);
        $this->entity->expects(self::once())
            ->method('getProducts')
            ->willReturn($this->createMock(ArrayCollection::class));
        $categoryRepository->expects(self::once())
            ->method('findOneByProduct')
            ->willReturn(new CategoryStub());
        $this->manager->expects(self::exactly(2))
            ->method('getRepository')
            ->withConsecutive([Category::class], [Product::class])
            ->willReturnOnConsecutiveCalls($categoryRepository, $productRepository);
        $product = $this->createMock(ProductStub::class);
        $product->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $productRepository->expects(self::once())
            ->method('findBy')
            ->with(['id' => [1]])
            ->willReturn([$product]);

        self::assertTrue($this->handler->process($this->entity));
    }

    public function supportedMethods(): array
    {
        return [['POST', true, true], ['PUT', true, true]];
    }

    public function testProcessSupportedRequestWithInvalidData(): void
    {
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');

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

        self::assertFalse($this->handler->process($this->entity));
    }

    public function testProcessValidData(): void
    {
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');

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

        $this->expectsAppendRemoveSortProducts();
        $this->expectsCategoryUnitPrecisionUpdate();

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $productRepository = $this->createMock(ProductRepository::class);
        $this->entity->expects(self::once())
            ->method('getProducts')
            ->willReturn($this->createMock(ArrayCollection::class));
        $categoryRepository->expects(self::once())
            ->method('findOneByProduct')
            ->willReturn(new CategoryStub());
        $this->manager->expects(self::exactly(2))
            ->method('getRepository')
            ->withConsecutive([Category::class], [Product::class])
            ->willReturnOnConsecutiveCalls($categoryRepository, $productRepository);
        $product = $this->createMock(ProductStub::class);
        $product->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $productRepository->expects(self::once())
            ->method('findBy')
            ->with(['id' => [1]])
            ->willReturn([$product]);
        $this->manager->expects(self::exactly(2))
            ->method('persist');
        $this->manager->expects(self::exactly(2))
            ->method('flush');

        self::assertTrue($this->handler->process($this->entity));
    }
}
