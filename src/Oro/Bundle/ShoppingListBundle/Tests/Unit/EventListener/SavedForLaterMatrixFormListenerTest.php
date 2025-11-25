<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\SavedForLaterMatrixFormListener;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionRow;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class SavedForLaterMatrixFormListenerTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private ObjectRepository&MockObject $repository;
    private FormInterface&MockObject $form;

    private RequestStack $requestStack;
    private SavedForLaterMatrixFormListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->requestStack->push(new Request());
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(ObjectRepository::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->registry->expects(self::any())
            ->method('getRepository')
            ->with(ShoppingList::class)
            ->willReturn($this->repository);

        $this->listener = new SavedForLaterMatrixFormListener($this->requestStack, $this->registry);
    }

    public function testOnFormDataSetNoSavedForLater(): void
    {
        $this->repository->expects(self::never())
            ->method('find');

        $this->listener->onFormDataSet(new FormProcessEvent($this->form, []));
    }

    public function testOnFormDataSetNoMatrixCollection(): void
    {
        $this->requestStack->getMainRequest()->attributes->set('savedForLaterGrid', true);

        $this->repository->expects(self::never())
            ->method('find');

        $this->listener->onFormDataSet(new FormProcessEvent($this->form, []));
    }

    public function testOnFormDataSetNoShoppingListId(): void
    {
        $this->requestStack->getMainRequest()->attributes->set('savedForLaterGrid', true);

        $this->repository->expects(self::never())
            ->method('find');

        $this->listener->onFormDataSet(new FormProcessEvent($this->form, []));
    }

    public function testOnFormDataSetNoShoppingList(): void
    {
        $this->requestStack->getMainRequest()->attributes->set('savedForLaterGrid', true);
        $this->requestStack->getMainRequest()->attributes->set('shoppingListId', 1);

        $this->repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $matrixCollection = new MatrixCollection();
        $event = new FormProcessEvent($this->form, $matrixCollection);

        $this->listener->onFormDataSet($event);

        self::assertSame($matrixCollection, $event->getData());
    }

    public function testOnFormDataSetEmptyLineItems(): void
    {
        $this->requestStack->getMainRequest()->attributes->set('savedForLaterGrid', true);
        $this->requestStack->getMainRequest()->attributes->set('shoppingListId', 1);

        $this->repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(new ShoppingList());

        $matrixCollection = new MatrixCollection();
        $event = new FormProcessEvent($this->form, $matrixCollection);

        $this->listener->onFormDataSet($event);

        self::assertSame($matrixCollection, $event->getData());
    }

    public function testOnFormDataSet(): void
    {
        $this->requestStack->getMainRequest()->attributes->set('savedForLaterGrid', true);
        $this->requestStack->getMainRequest()->attributes->set('shoppingListId', 1);

        $lineItem = new LineItem();
        $lineItem->setUnit((new ProductUnit())->setCode('each'));
        $lineItem->setProduct((new ProductStub())->setId(2));
        $lineItem->setQuantity(5);

        $shoppingList = new ShoppingList();
        $shoppingList->addSavedForLaterLineItem($lineItem);

        $this->repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($shoppingList);

        $column = new MatrixCollectionColumn();
        $column->product = (new ProductStub())->setId(2);
        $row = new MatrixCollectionRow();
        $row->columns = [$column];
        $matrixCollection = new MatrixCollection();
        $matrixCollection->unit = (new ProductUnit())->setCode('each');
        $matrixCollection->rows = [$row];

        $event = new FormProcessEvent($this->form, $matrixCollection);

        self::assertNull($event->getData()->rows[0]->columns[0]->quantity);

        $this->listener->onFormDataSet($event);

        self::assertEquals(5, $event->getData()->rows[0]->columns[0]->quantity);
    }
}
