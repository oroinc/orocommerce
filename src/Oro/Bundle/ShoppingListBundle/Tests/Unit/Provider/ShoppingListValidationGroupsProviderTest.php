<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Provider;

use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListValidationGroupResolverInterface;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListValidationGroupsProvider;
use PHPUnit\Framework\TestCase;

final class ShoppingListValidationGroupsProviderTest extends TestCase
{
    public function testGetAllValidationGroupsReturnsApplicableGroups(): void
    {
        $applicableResolver = $this->createMock(ShoppingListValidationGroupResolverInterface::class);
        $notApplicableResolver = $this->createMock(ShoppingListValidationGroupResolverInterface::class);

        $applicableResolver->expects(self::once())
            ->method('isApplicable')
            ->willReturn(true);
        $applicableResolver->expects(self::once())
            ->method('getValidationGroupName')
            ->willReturn('group_checkout');

        $notApplicableResolver->expects(self::once())
            ->method('isApplicable')
            ->willReturn(false);
        $notApplicableResolver->expects(self::never())
            ->method('getValidationGroupName');

        $provider = new ShoppingListValidationGroupsProvider([$applicableResolver, $notApplicableResolver]);

        self::assertSame(['group_checkout'], $provider->getAllValidationGroups());
    }

    public function testGetValidationGroupByTypeReturnsGroupName(): void
    {
        $checkoutResolver = $this->createMock(ShoppingListValidationGroupResolverInterface::class);
        $rfqResolver = $this->createMock(ShoppingListValidationGroupResolverInterface::class);

        $checkoutResolver->expects(self::once())
            ->method('getType')
            ->willReturn('checkout');
        $checkoutResolver->expects(self::once())
            ->method('isApplicable')
            ->willReturn(true);
        $checkoutResolver->expects(self::once())
            ->method('getValidationGroupName')
            ->willReturn('group_checkout');

        $rfqResolver->expects(self::never())
            ->method('getType');
        $rfqResolver->expects(self::never())
            ->method('isApplicable');

        $provider = new ShoppingListValidationGroupsProvider([$checkoutResolver, $rfqResolver]);

        self::assertSame('group_checkout', $provider->getValidationGroupByType('checkout'));
    }

    public function testGetValidationGroupByTypeWhenResolverNotApplicableThrowsException(): void
    {
        $checkoutResolver = $this->createMock(ShoppingListValidationGroupResolverInterface::class);

        $checkoutResolver->expects(self::once())
            ->method('getType')
            ->willReturn('checkout');
        $checkoutResolver->expects(self::once())
            ->method('isApplicable')
            ->willReturn(false);
        $checkoutResolver->expects(self::never())
            ->method('getValidationGroupName');

        $provider = new ShoppingListValidationGroupsProvider([$checkoutResolver]);

        $this->expectExceptionObject(new \InvalidArgumentException('Invalid validation group type: checkout'));

        $provider->getValidationGroupByType('checkout');
    }

    public function testGetValidationGroupByTypeWhenTypeNotFoundThrowsException(): void
    {
        $checkoutResolver = $this->createMock(ShoppingListValidationGroupResolverInterface::class);

        $checkoutResolver->expects(self::once())
            ->method('getType')
            ->willReturn('checkout');
        $checkoutResolver->expects(self::never())
            ->method('isApplicable');

        $provider = new ShoppingListValidationGroupsProvider([$checkoutResolver]);

        $this->expectExceptionObject(new \InvalidArgumentException('Invalid validation group type: unknown'));

        $provider->getValidationGroupByType('unknown');
    }
}
