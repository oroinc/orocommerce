<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Callback;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Datagrid\Callback\ShoppingListActionConfigurationCallback;

class ShoppingListActionConfigurationCallbackTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ProductMatrixAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productMatrixAvailabilityProvider;

    /** @var ShoppingListActionConfigurationCallback */
    private $callback;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->productMatrixAvailabilityProvider = $this->createMock(ProductMatrixAvailabilityProvider::class);

        $this->callback = new ShoppingListActionConfigurationCallback(
            $this->doctrineHelper,
            $this->productMatrixAvailabilityProvider
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
        $record = new ResultRecord(['notes' => $notes]);

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
            'productId' => 3
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
            'productId' => 3
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
}
