<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Visibility\Checker;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Checker\FrontendCategoryVisibilityChecker;
use Oro\Bundle\VisibilityBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;

class FrontendCategoryVisibilityCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var CategoryVisibilityResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryVisibilityResolver;

    /** @var CustomerUserRelationsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $customerUserRelationsProvider;

    /** @var FrontendCategoryVisibilityChecker */
    private $categoryVisibilityChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->categoryVisibilityResolver = $this->createMock(CategoryVisibilityResolverInterface::class);
        $this->customerUserRelationsProvider = $this->createMock(CustomerUserRelationsProvider::class);

        $this->categoryVisibilityChecker = new FrontendCategoryVisibilityChecker(
            $this->tokenAccessor,
            $this->categoryVisibilityResolver,
            $this->customerUserRelationsProvider
        );
    }

    public static function categoryVisibilityDataProvider(): array
    {
        return [[false], [true]];
    }

    /**
     * @dataProvider categoryVisibilityDataProvider
     */
    public function testNoUser(bool $visible): void
    {
        $category = new Category();

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn(null);
        $this->customerUserRelationsProvider->expects(self::once())
            ->method('getCustomer')
            ->with(self::isNull())
            ->willReturn(null);
        $this->customerUserRelationsProvider->expects(self::once())
            ->method('getCustomerGroup')
            ->with(self::isNull())
            ->willReturn(null);
        $this->categoryVisibilityResolver->expects(self::once())
            ->method('isCategoryVisible')
            ->with(self::identicalTo($category))
            ->willReturn($visible);

        self::assertSame($visible, $this->categoryVisibilityChecker->isCategoryVisible($category));
    }

    /**
     * @dataProvider categoryVisibilityDataProvider
     */
    public function testNoCustomerAndNoCustomerGroup(bool $visible): void
    {
        $category = new Category();
        $user = new CustomerUser();

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->customerUserRelationsProvider->expects(self::once())
            ->method('getCustomer')
            ->with(self::identicalTo($user))
            ->willReturn(null);
        $this->customerUserRelationsProvider->expects(self::once())
            ->method('getCustomerGroup')
            ->with(self::identicalTo($user))
            ->willReturn(null);
        $this->categoryVisibilityResolver->expects(self::once())
            ->method('isCategoryVisible')
            ->with(self::identicalTo($category))
            ->willReturn($visible);

        self::assertSame($visible, $this->categoryVisibilityChecker->isCategoryVisible($category));
    }

    /**
     * @dataProvider categoryVisibilityDataProvider
     */
    public function testHasCustomer(bool $visible): void
    {
        $category = new Category();
        $user = new CustomerUser();
        $customer = new Customer();

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->customerUserRelationsProvider->expects(self::once())
            ->method('getCustomer')
            ->with(self::identicalTo($user))
            ->willReturn($customer);
        $this->customerUserRelationsProvider->expects(self::never())
            ->method('getCustomerGroup');
        $this->categoryVisibilityResolver->expects(self::once())
            ->method('isCategoryVisibleForCustomer')
            ->with(self::identicalTo($category), self::identicalTo($customer))
            ->willReturn($visible);

        self::assertSame($visible, $this->categoryVisibilityChecker->isCategoryVisible($category));
    }

    /**
     * @dataProvider categoryVisibilityDataProvider
     */
    public function testHasCustomerGroup(bool $visible): void
    {
        $category = new Category();
        $user = new CustomerUser();
        $customerGroup = new CustomerGroup();

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->customerUserRelationsProvider->expects(self::once())
            ->method('getCustomer')
            ->with(self::identicalTo($user))
            ->willReturn(null);
        $this->customerUserRelationsProvider->expects(self::once())
            ->method('getCustomerGroup')
            ->with(self::identicalTo($user))
            ->willReturn($customerGroup);
        $this->categoryVisibilityResolver->expects(self::once())
            ->method('isCategoryVisibleForCustomerGroup')
            ->with(self::identicalTo($category), self::identicalTo($customerGroup))
            ->willReturn($visible);

        self::assertSame($visible, $this->categoryVisibilityChecker->isCategoryVisible($category));
    }
}
