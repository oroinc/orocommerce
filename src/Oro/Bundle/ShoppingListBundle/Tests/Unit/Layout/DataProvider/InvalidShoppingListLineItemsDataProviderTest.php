<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\InvalidShoppingListLineItemsDataProvider;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class InvalidShoppingListLineItemsDataProviderTest extends TestCase
{
    private InvalidShoppingListLineItemsProvider&MockObject $provider;

    private InvalidShoppingListLineItemsDataProvider $dataProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = $this->createMock(InvalidShoppingListLineItemsProvider::class);

        $this->dataProvider = new InvalidShoppingListLineItemsDataProvider($this->provider);
    }

    public function testGetInvalidLineItemsIds(): void
    {
        $shoppingList = new ShoppingList();

        $this->provider->expects(self::once())
            ->method('getInvalidLineItemsIds')
            ->with(new ArrayCollection([]))
            ->willReturn([2, 1]);

        self::assertSame([2, 1], $this->dataProvider->getInvalidLineItemsIds($shoppingList));
    }

    public function testGetInvalidLineItemsIdsWithValidationGroup(): void
    {
        $shoppingList = new ShoppingList();

        $this->provider->expects(self::once())
            ->method('getInvalidLineItemsIds')
            ->with(new ArrayCollection([]), 'checkout')
            ->willReturn([2, 1]);

        self::assertSame(
            [2, 1],
            $this->dataProvider->getInvalidLineItemsIds(
                $shoppingList,
                'checkout'
            )
        );
    }

    public function testGetInvalidLineItemsIdsBySeverity(): void
    {
        $shoppingList = new ShoppingList();

        $this->provider->expects(self::once())
            ->method('getInvalidLineItemsIdsBySeverity')
            ->with(new ArrayCollection([]))
            ->willReturn(['errors' => [2, 1, 'warnings' => []]]);

        self::assertSame(
            ['errors' => [2, 1, 'warnings' => []]],
            $this->dataProvider->getInvalidLineItemsIdsBySeverity($shoppingList)
        );
    }

    public function testGetInvalidLineItemsIdsBySeverityWithValidationGroup(): void
    {
        $shoppingList = new ShoppingList();

        $this->provider->expects(self::once())
            ->method('getInvalidLineItemsIdsBySeverity')
            ->with(new ArrayCollection([]), 'rfq')
            ->willReturn(['errors' => [2, 1, 'warnings' => []]]);

        self::assertSame(
            ['errors' => [2, 1, 'warnings' => []]],
            $this->dataProvider->getInvalidLineItemsIdsBySeverity(
                $shoppingList,
                'rfq'
            )
        );
    }
}
