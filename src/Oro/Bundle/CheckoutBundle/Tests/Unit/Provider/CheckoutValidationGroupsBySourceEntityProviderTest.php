<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Provider\CheckoutValidationGroupsBySourceEntityProvider;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemsHolderStub;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\GroupSequence;

class CheckoutValidationGroupsBySourceEntityProviderTest extends TestCase
{
    private CheckoutValidationGroupsBySourceEntityProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $entityAliasResolver
            ->method('getAlias')
            ->willReturnCallback(static fn (string $value) => strtolower(substr($value, strrpos($value, '\\') + 1)));

        $this->provider = new CheckoutValidationGroupsBySourceEntityProvider($entityAliasResolver);
    }

    /**
     * @dataProvider getValidationGroupsBySourceEntityDataProvider
     */
    public function testGetValidationGroupsBySourceEntity(
        array $validationGroups,
        CheckoutSourceEntityInterface|string|null $checkoutSourceEntity,
        array $expected
    ): void {
        self::assertEquals(
            $expected,
            $this->provider->getValidationGroupsBySourceEntity($validationGroups, $checkoutSourceEntity)
        );
    }

    public function getValidationGroupsBySourceEntityDataProvider(): iterable
    {
        yield 'empty' => [[], null, []];

        yield 'not empty groups' => [['group1', 'group2'], null, ['group1', 'group2']];

        yield 'not empty groups with placeholder' => [['group1%from_alias%', 'group2'], null, ['group1', 'group2']];

        yield 'not empty groups with placeholder, with source entity' => [
            ['group1%from_alias%', 'group2'],
            new ShoppingList(),
            ['group1_from_shoppinglist', 'group2'],
        ];

        yield 'not empty groups with placeholder, with source entity class' => [
            ['group1%from_alias%', 'group2'],
            ProductLineItemsHolderStub::class,
            ['group1_from_productlineitemsholderstub', 'group2'],
        ];

        yield 'not empty groups with group sequence, with source entity' => [
            [['group1%from_alias%', 'group2']],
            new ShoppingList(),
            [new GroupSequence(['group1_from_shoppinglist', 'group2'])],
        ];

        yield 'not empty groups with group sequence, with source entity class' => [
            [['group1%from_alias%', 'group2']],
            ProductLineItemsHolderStub::class,
            [new GroupSequence(['group1_from_productlineitemsholderstub', 'group2'])],
        ];
    }
}
