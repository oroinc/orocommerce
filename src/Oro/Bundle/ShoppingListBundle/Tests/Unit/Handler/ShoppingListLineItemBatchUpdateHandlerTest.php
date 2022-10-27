<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemBatchUpdateHandler;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Model\LineItemModel;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ShoppingListLineItemBatchUpdateHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LineItemRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemRepository;

    /** @var ProductUnitRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnitRepository;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListManager;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var ShoppingListLineItemBatchUpdateHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->lineItemRepository = $this->createMock(LineItemRepository::class);
        $this->productUnitRepository = $this->createMock(ProductUnitRepository::class);
        $this->entityManager = $this->createMock(ObjectManager::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturnMap(
                [
                    [LineItem::class, $this->lineItemRepository],
                    [ProductUnit::class, $this->productUnitRepository],
                ]
            );

        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->handler = new ShoppingListLineItemBatchUpdateHandler(
            $this->doctrineHelper,
            $this->shoppingListManager,
            $this->validator
        );
    }

    public function testProcess(): void
    {
        $model1 = new LineItemModel(1001, 5.55, 'item');
        $model2 = new LineItemModel(2002, 7.77, 'set');

        $shoppingList = new ShoppingList();

        $product1 = new Product();
        $product1->setSku('pr1');

        $product2 = new Product();
        $product2->setSku('pr2');

        $item1 = $this->getEntity(LineItem::class, ['id' => $model1->getId(), 'product' => $product1]);
        $item2 = $this->getEntity(LineItem::class, ['id' => $model2->getId(), 'product' => $product2]);

        $this->lineItemRepository->expects($this->once())
            ->method('findBy')
            ->with(['id' => [$model1->getId(), $model2->getId()]])
            ->willReturn([$item1, $item2]);

        $productUnit1 = new ProductUnit();
        $productUnit1->setCode($model1->getUnitCode());

        $productUnit2 = new ProductUnit();
        $productUnit2->setCode($model2->getUnitCode());

        $this->productUnitRepository->expects($this->once())
            ->method('getProductsUnitsByCodes')
            ->with([$product1, $product2], [$model1->getUnitCode(), $model2->getUnitCode()])
            ->willReturn([$model1->getUnitCode() => $productUnit1, $model2->getUnitCode() => $productUnit2]);

        $this->shoppingListManager->expects($this->exactly(2))
            ->method('addLineItem')
            ->withConsecutive(
                [$item1, $shoppingList, false, true],
                [$item2, $shoppingList, false, true]
            );

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->handler->process([$model1, $model2], $shoppingList);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testProcessErrors(): void
    {
        $model1 = new LineItemModel(1001, 5.55, 'item');

        $shoppingList = new ShoppingList();

        $product1 = new Product();
        $product1->setSku('pr1');

        $item1 = $this->getEntity(LineItem::class, ['id' => $model1->getId(), 'product' => $product1]);

        $this->lineItemRepository->expects($this->once())
            ->method('findBy')
            ->with(['id' => [$model1->getId()]])
            ->willReturn([$item1]);

        $productUnit1 = new ProductUnit();
        $productUnit1->setCode($model1->getUnitCode());

        $this->productUnitRepository->expects($this->once())
            ->method('getProductsUnitsByCodes')
            ->with([$product1], [$model1->getUnitCode()])
            ->willReturn([$model1->getUnitCode() => $productUnit1]);

        $this->shoppingListManager->expects($this->once())
            ->method('addLineItem')
            ->withConsecutive(
                [$item1, $shoppingList, false, true],
            );

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($shoppingList)
            ->willReturn(new ConstraintViolationList(
                [
                    new ConstraintViolation('Test message', 'Test template', [], 'root', 'name', 'data')
                ]
            ));

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager->expects($this->never())
            ->method('flush');

        $result = $this->handler->process([$model1], $shoppingList);

        $this->assertIsArray($result);
        $this->assertEquals(['Test message'], $result);
    }
}
