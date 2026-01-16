<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Resolver;

use Oro\Bundle\CheckoutBundle\Condition\IsWorkflowStartFromShoppingListAllowed;
use Oro\Bundle\CheckoutBundle\Resolver\ShoppingListToCheckoutValidationGroupResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ShoppingListToCheckoutValidationGroupResolverTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private IsWorkflowStartFromShoppingListAllowed&MockObject $isWorkflowStartFromShoppingListAllowed;
    private ShoppingListToCheckoutValidationGroupResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->isWorkflowStartFromShoppingListAllowed = $this->createMock(
            IsWorkflowStartFromShoppingListAllowed::class
        );

        $this->resolver = new ShoppingListToCheckoutValidationGroupResolver(
            $this->authorizationChecker,
            $this->isWorkflowStartFromShoppingListAllowed
        );
    }

    public function testGetType(): void
    {
        self::assertSame(
            ShoppingListToCheckoutValidationGroupResolver::TYPE,
            $this->resolver->getType()
        );
    }

    public function testGetValidationGroupName(): void
    {
        self::assertSame('datagrid_line_items_data_for_checkout', $this->resolver->getValidationGroupName());
    }

    public function testIsApplicableReturnsFalseWhenAclDenied(): void
    {
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:commerce@Oro\Bundle\CheckoutBundle\Entity\Checkout')
            ->willReturn(false);
        $this->isWorkflowStartFromShoppingListAllowed->expects(self::never())
            ->method('isAllowedForAny');

        self::assertFalse($this->resolver->isApplicable());
    }

    public function testIsApplicableReturnsFalseWhenWorkflowDenied(): void
    {
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:commerce@Oro\Bundle\CheckoutBundle\Entity\Checkout')
            ->willReturn(true);
        $this->isWorkflowStartFromShoppingListAllowed->expects(self::once())
            ->method('isAllowedForAny')
            ->willReturn(false);

        self::assertFalse($this->resolver->isApplicable());
    }

    public function testIsApplicableReturnsTrueWhenChecksPassed(): void
    {
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:commerce@Oro\Bundle\CheckoutBundle\Entity\Checkout')
            ->willReturn(true);
        $this->isWorkflowStartFromShoppingListAllowed->expects(self::once())
            ->method('isAllowedForAny')
            ->willReturn(true);

        self::assertTrue($this->resolver->isApplicable());
    }
}
