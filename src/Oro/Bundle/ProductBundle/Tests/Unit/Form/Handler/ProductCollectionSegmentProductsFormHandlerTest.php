<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Form\Handler\ProductCollectionSegmentProductsFormHandler;
use Oro\Bundle\ProductBundle\Handler\CollectionSortOrderHandler;
use Oro\Bundle\ProductBundle\Service\ProductCollectionSegmentManipulator;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductCollectionSegmentProductsFormHandlerTest extends TestCase
{
    private ProductCollectionSegmentManipulator|MockObject $collectionSegmentManipulator;

    private CollectionSortOrderHandler|MockObject $collectionSortOrderHandler;

    private ProductCollectionSegmentProductsFormHandler $handler;

    private EntityManager|MockObject $entityManager;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->collectionSegmentManipulator = $this->createMock(ProductCollectionSegmentManipulator::class);
        $this->collectionSortOrderHandler = $this->createMock(CollectionSortOrderHandler::class);

        $this->handler = new ProductCollectionSegmentProductsFormHandler(
            $managerRegistry,
            $this->collectionSegmentManipulator,
            $this->collectionSortOrderHandler
        );

        $this->entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with(Segment::class)
            ->willReturn($this->entityManager);
    }

    public function testProcessWhenNotSegment(): void
    {
        $data = new \stdClass();

        $this->expectError();
        $this->expectErrorMessage(
            sprintf(
                '"%s()" expects parameter 1 to be an instance of "%s", "%s" given.',
                ProductCollectionSegmentProductsFormHandler::class . '::process',
                Segment::class,
                get_debug_type($data)
            )
        );

        $this->handler->process(
            $data,
            $this->createMock(FormInterface::class),
            $this->createMock(Request::class)
        );
    }

    public function testProcessWhenMethodNotPut(): void
    {
        $data = new Segment();
        $form = $this->createMock(FormInterface::class);

        $form
            ->expects(self::never())
            ->method(self::anything());

        self::assertFalse(
            $this->handler->process(
                $data,
                $form,
                $this->createMock(Request::class)
            )
        );
    }

    public function testProcessWhenNotValid(): void
    {
        $data = new Segment();
        $form = $this->createMock(FormInterface::class);

        $formName = 'sample_form';
        $form
            ->expects(self::any())
            ->method('getName')
            ->willReturn($formName);

        $form
            ->expects(self::once())
            ->method('submit');

        $form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $request = Request::create('/sample-uri', Request::METHOD_PUT);

        self::assertFalse(
            $this->handler->process(
                $data,
                $form,
                $request
            )
        );
    }

    public function testProcessValidWhenEmptyData(): void
    {
        $segment = new Segment();
        $form = $this->createMock(FormInterface::class);

        $formName = 'sample_form';
        $form
            ->expects(self::any())
            ->method('getName')
            ->willReturn($formName);
        $form
            ->expects(self::once())
            ->method('submit');
        $form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $appendProductsForm = $this->createMock(FormInterface::class);
        $removeProductsForm = $this->createMock(FormInterface::class);
        $sortOrderForm = $this->createMock(FormInterface::class);

        $form
            ->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['appendProducts', $appendProductsForm],
                ['removeProducts', $removeProductsForm],
                ['sortOrder', $sortOrderForm],
            ]);

        $appendProducts = [];
        $removeProducts = [];
        $sortOrderValues = [];
        $appendProductsForm
            ->expects(self::any())
            ->method('getData')
            ->willReturn($appendProducts);
        $removeProductsForm
            ->expects(self::any())
            ->method('getData')
            ->willReturn($removeProducts);
        $sortOrderForm
            ->expects(self::any())
            ->method('getData')
            ->willReturn($sortOrderValues);

        $includedProducts = [];
        $excludedProducts = [];
        $this->collectionSegmentManipulator
            ->expects(self::once())
            ->method('updateManuallyManagedProducts')
            ->with($segment, $appendProducts, $removeProducts)
            ->willReturn([$includedProducts, $excludedProducts]);

        $sortOrderEntities = [];
        $this->collectionSortOrderHandler
            ->expects(self::once())
            ->method('updateSegmentSortOrders')
            ->with($sortOrderEntities, $segment);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($segment);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $request = Request::create('/sample-uri', Request::METHOD_PUT);

        self::assertTrue(
            $this->handler->process(
                $segment,
                $form,
                $request
            )
        );
    }

    public function testProcessValidWhenNotEmptyData(): void
    {
        $segment = new Segment();
        $form = $this->createMock(FormInterface::class);

        $formName = 'sample_form';
        $form
            ->expects(self::any())
            ->method('getName')
            ->willReturn($formName);
        $form
            ->expects(self::once())
            ->method('submit');
        $form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $appendProductsForm = $this->createMock(FormInterface::class);
        $removeProductsForm = $this->createMock(FormInterface::class);
        $sortOrderForm = $this->createMock(FormInterface::class);

        $form
            ->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['appendProducts', $appendProductsForm],
                ['removeProducts', $removeProductsForm],
                ['sortOrder', $sortOrderForm],
            ]);

        $appendProducts = [(new ProductStub())->setId(10), (new ProductStub())->setId(20)];
        $removeProducts = [(new ProductStub())->setId(30)];
        $sortOrderValues = [10 => ['data' => (new CollectionSortOrder())->setSortOrder(11)]];
        $appendProductsForm
            ->expects(self::any())
            ->method('getData')
            ->willReturn($appendProducts);
        $removeProductsForm
            ->expects(self::any())
            ->method('getData')
            ->willReturn($removeProducts);
        $sortOrderForm
            ->expects(self::any())
            ->method('getData')
            ->willReturn($sortOrderValues);

        $includedProducts = [10, 20];
        $excludedProducts = [30];
        $this->collectionSegmentManipulator
            ->expects(self::once())
            ->method('updateManuallyManagedProducts')
            ->with($segment, [10, 20], [30])
            ->willReturn([$includedProducts, $excludedProducts]);

        $this->collectionSortOrderHandler
            ->expects(self::once())
            ->method('updateSegmentSortOrders')
            ->with(array_column($sortOrderValues, 'data'), $segment);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($segment);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $request = Request::create('/sample-uri', Request::METHOD_PUT);

        self::assertTrue(
            $this->handler->process(
                $segment,
                $form,
                $request
            )
        );
    }

    public function testProcessSortOrderIsUnsetForExcludedProduct(): void
    {
        $segment = new Segment();
        $form = $this->createMock(FormInterface::class);

        $formName = 'sample_form';
        $form
            ->expects(self::any())
            ->method('getName')
            ->willReturn($formName);
        $form
            ->expects(self::once())
            ->method('submit');
        $form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $appendProductsForm = $this->createMock(FormInterface::class);
        $removeProductsForm = $this->createMock(FormInterface::class);
        $sortOrderForm = $this->createMock(FormInterface::class);

        $form
            ->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['appendProducts', $appendProductsForm],
                ['removeProducts', $removeProductsForm],
                ['sortOrder', $sortOrderForm],
            ]);

        $appendProducts = [(new ProductStub())->setId(10), (new ProductStub())->setId(20)];
        $removeProducts = [(new ProductStub())->setId(30)];
        $sortOrderValues = [30 => ['data' => (new CollectionSortOrder())->setSortOrder(31)]];
        $appendProductsForm
            ->expects(self::any())
            ->method('getData')
            ->willReturn($appendProducts);
        $removeProductsForm
            ->expects(self::any())
            ->method('getData')
            ->willReturn($removeProducts);
        $sortOrderForm
            ->expects(self::any())
            ->method('getData')
            ->willReturn($sortOrderValues);

        $includedProducts = [10, 20];
        $excludedProducts = [30];
        $this->collectionSegmentManipulator
            ->expects(self::once())
            ->method('updateManuallyManagedProducts')
            ->with($segment, [10, 20], [30])
            ->willReturn([$includedProducts, $excludedProducts]);

        $this->collectionSortOrderHandler
            ->expects(self::once())
            ->method('updateSegmentSortOrders')
            ->with([(new CollectionSortOrder())->setSortOrder(null)], $segment);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($segment);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $request = Request::create('/sample-uri', Request::METHOD_PUT);

        self::assertTrue(
            $this->handler->process(
                $segment,
                $form,
                $request
            )
        );
    }
}
