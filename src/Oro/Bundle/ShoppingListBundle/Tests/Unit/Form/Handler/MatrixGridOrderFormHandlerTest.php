<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Event\EventDispatcher;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\MatrixGridOrderFormHandler;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class MatrixGridOrderFormHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var MatrixGridOrderManager|\PHPUnit\Framework\MockObject\MockObject */
    private $matrixGridOrderManager;

    /** @var ShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListManager;

    /** @var MatrixGridOrderFormHandler */
    private $matrixGridOrderFormHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->matrixGridOrderManager = $this->createMock(MatrixGridOrderManager::class);
        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);

        $this->matrixGridOrderFormHandler = new MatrixGridOrderFormHandler(
            $this->eventDispatcher,
            $this->doctrineHelper,
            $this->matrixGridOrderManager,
            $this->shoppingListManager,
        );
    }

    public function testProcessWithoutShoppingList(): void
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $collection = new MatrixCollection();
        $collection->product = new Product();
        $result = $this->matrixGridOrderFormHandler->process($collection, $form, $request);

        $this->assertFalse($result);
    }

    public function testProcessWithoutProduct(): void
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $collection = new MatrixCollection();
        $collection->shoppingList = new ShoppingList();
        $result = $this->matrixGridOrderFormHandler->process($collection, $form, $request);

        $this->assertFalse($result);
    }

    public function testProcessInvalidData(): void
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('The "data" argument should be instance of the "%s" entity', MatrixCollection::class)
        );

        $this->matrixGridOrderFormHandler->process(new \stdClass(), $form, $request);
    }

    public function testProcessFormProcessMethodNotPost(): void
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);

        $data = new MatrixCollection();
        $data->product = new Product();
        $data->shoppingList = new ShoppingList();

        $event = new FormProcessEvent($form, $data);
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($event, Events::BEFORE_FORM_DATA_SET)
            ->willReturn($event);

        $result = $this->matrixGridOrderFormHandler->process($data, $form, $request);

        $this->assertFalse($result);
    }

    public function testProcessFormProcessInterruptedBeforeFormSubmit(): void
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $data = new MatrixCollection();
        $data->product = new Product();
        $data->shoppingList = new ShoppingList();

        $event1 = new FormProcessEvent($form, $data);
        $event2 = new FormProcessEvent($form, $data);
        $this->eventDispatcher
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$event1, Events::BEFORE_FORM_DATA_SET],
                [$event2, Events::BEFORE_FORM_SUBMIT]
            )
            ->willReturnOnConsecutiveCalls(
                $event1,
                $this->callback(static function () use ($event2) {
                    $event2->interruptFormProcess();

                    return $event2;
                })
            );

        $result = $this->matrixGridOrderFormHandler->process($data, $form, $request);

        $this->assertFalse($result);
    }
}
