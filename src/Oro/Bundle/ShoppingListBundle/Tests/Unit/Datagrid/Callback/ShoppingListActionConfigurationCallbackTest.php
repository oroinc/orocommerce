<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Callback;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Datagrid\Callback\ShoppingListActionConfigurationCallback;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ShoppingListActionConfigurationCallbackTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ProductMatrixAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productMatrixAvailabilityProvider;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var ShoppingListActionConfigurationCallback */
    private $callback;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->productMatrixAvailabilityProvider = $this->createMock(ProductMatrixAvailabilityProvider::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->callback = new ShoppingListActionConfigurationCallback(
            $this->doctrineHelper,
            $this->productMatrixAvailabilityProvider,
            $this->authorizationChecker
        );
    }

    /**
     * @dataProvider checkActionsDataProvider
     *
     * @param string|null $notes
     * @param bool $addNotes
     */
    public function testCheckActionsIsNotConfigurable(?string $notes, bool $addNotes): void
    {
        $record = new ResultRecord(['notes' => $notes, 'shoppingListId' => 1]);

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityRepositoryForClass');

        $this->productMatrixAvailabilityProvider
            ->expects($this->never())
            ->method('isMatrixFormAvailable');

        $this->assertEquals(
            [
                'update_configurable' => false,
                'add_notes' => $addNotes,
                'edit_notes' => false,
            ],
            $this->callback->checkActions($record)
        );
    }

    /**
     * @return array[]
     */
    public function checkActionsDataProvider(): array
    {
        return [
            [
                'notes' => 'test notes',
                'addNotes' => false,
            ],
            [
                'notes' => null,
                'addNotes' => true,
            ],
            [
                'notes' => '',
                'addNotes' => true,
            ],
        ];
    }

    public function testCheckActionsWithoutProduct(): void
    {
        $record = new ResultRecord([
            'isConfigurable' => true,
            'productId' => 3,
            'shoppingListId' => 1,
        ]);

        $product = null;

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(3)
            ->willReturn($product);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(Product::class)
            ->willReturn($repository);

        $this->productMatrixAvailabilityProvider
            ->expects($this->never())
            ->method('isMatrixFormAvailable');

        $this->assertEquals(
            [
                'update_configurable' => false,
                'add_notes' => false,
                'edit_notes' => false,
            ],
            $this->callback->checkActions($record)
        );
    }

    /**
     * @dataProvider isMatrixFormAvailableDataProvider
     * @param bool $isAvailable
     */
    public function testCheckActions(bool $isAvailable): void
    {
        $record = new ResultRecord([
            'isConfigurable' => true,
            'productId' => 3,
            'shoppingListId' => 1
        ]);

        $product = new Product();

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(3)
            ->willReturn($product);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(Product::class)
            ->willReturn($repository);

        $this->productMatrixAvailabilityProvider
            ->expects($this->once())
            ->method('isMatrixFormAvailable')
            ->with($product)
            ->willReturn($isAvailable);

        $this->assertEquals(
            [
                'update_configurable' => $isAvailable,
                'add_notes' => false,
                'edit_notes' => false,
            ],
            $this->callback->checkActions($record)
        );
    }

    /**
     * @return array
     */
    public function isMatrixFormAvailableDataProvider(): array
    {
        return [
            'available' => [true],
            'not available' => [false],
        ];
    }

    public function testCheckActionsAccessDenied(): void
    {
        $shoppingList1 = $this->getShoppingList(1);
        $record1 = new ResultRecord([
            'shoppingListId' => $shoppingList1->getId(),
            'productId' => 1,
        ]);
        $record2 = new ResultRecord([
            'shoppingListId' => $shoppingList1->getId(),
            'productId' => 2,
        ]);

        $shoppingList2 = $this->getShoppingList(2);
        $record3 = new ResultRecord([
            'shoppingListId' => $shoppingList2->getId(),
            'productId' => 1,
        ]);
        $record4 = new ResultRecord([
            'shoppingListId' => $shoppingList2->getId(),
            'productId' => 3,
        ]);

        $this->doctrineHelper
            ->expects($this->exactly(2))
            ->method('getEntityReference')
            ->withConsecutive(
                [ShoppingList::class, $shoppingList1->getId()],
                [ShoppingList::class, $shoppingList2->getId()],
            )
            ->willReturnOnConsecutiveCalls($shoppingList1, $shoppingList2);

        $this->authorizationChecker
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                ['oro_shopping_list_frontend_update', $shoppingList1],
                ['oro_shopping_list_frontend_update', $shoppingList2],
            )
            ->willReturnOnConsecutiveCalls(false, true);

        $expectedResults1 = [
            'add_notes' => false,
            'edit_notes' => false,
            'update_configurable' => false,
            'delete' => false,
        ];

        $expectedResults2 = [
            'add_notes' => true,
            'edit_notes' => false,
            'update_configurable' => false,
        ];

        // Ensure that the method "isGranted" should be called only once for the Shopping List 1
        $this->assertEquals($expectedResults1, $this->callback->checkActions($record1));
        $this->assertEquals($expectedResults1, $this->callback->checkActions($record2));

        // Ensure that the method "isGranted" should be called only once for the Shopping List 2
        $this->assertEquals($expectedResults2, $this->callback->checkActions($record3));
        $this->assertEquals($expectedResults2, $this->callback->checkActions($record4));
    }

    /**
     * @param int $id
     * @return ShoppingListStub
     */
    private function getShoppingList(int $id): ShoppingListStub
    {
        $shoppingList = new ShoppingListStub();
        $shoppingList->setId($id);

        return $shoppingList;
    }
}
