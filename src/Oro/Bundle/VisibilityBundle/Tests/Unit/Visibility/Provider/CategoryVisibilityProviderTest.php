<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Visibility\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\VisibilityBundle\Visibility\Provider\CategoryVisibilityProvider;
use Oro\Bundle\VisibilityBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;

class CategoryVisibilityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CategoryVisibilityResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryVisibilityResolver;

    /** @var CustomerUserRelationsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $customerUserRelationsProvider;

    /** @var CategoryVisibilityProvider */
    private $categoryVisibilityProvider;

    protected function setUp(): void
    {
        $this->categoryVisibilityResolver = $this->createMock(CategoryVisibilityResolverInterface::class);
        $this->customerUserRelationsProvider = $this->createMock(CustomerUserRelationsProvider::class);

        $this->categoryVisibilityProvider = new CategoryVisibilityProvider(
            $this->categoryVisibilityResolver,
            $this->customerUserRelationsProvider
        );
    }

    public function testGetHiddenCategoryIdsForCustomer()
    {
        $customerUser = $this->createMock(CustomerUser::class);
        $customer = $this->createMock(Customer::class);
        $hiddenCategoryIds = [1, 2, 3];

        $this->customerUserRelationsProvider->expects($this->once())
            ->method('getCustomer')
            ->with($this->identicalTo($customerUser))
            ->willReturn($customer);
        $this->categoryVisibilityResolver->expects($this->once())
            ->method('getHiddenCategoryIdsForCustomer')
            ->with($this->identicalTo($customer))
            ->willReturn($hiddenCategoryIds);

        $this->assertSame(
            $hiddenCategoryIds,
            $this->categoryVisibilityProvider->getHiddenCategoryIds($customerUser)
        );
    }

    public function testGetHiddenCategoryIdsForCustomerGroup()
    {
        $customerUser = $this->createMock(CustomerUser::class);
        $customerGroup = $this->createMock(CustomerGroup::class);
        $hiddenCategoryIds = [1, 2, 3];

        $this->customerUserRelationsProvider->expects($this->once())
            ->method('getCustomer')
            ->with($this->identicalTo($customerUser))
            ->willReturn(null);
        $this->customerUserRelationsProvider->expects($this->once())
            ->method('getCustomerGroup')
            ->with($this->identicalTo($customerUser))
            ->willReturn($customerGroup);
        $this->categoryVisibilityResolver->expects($this->once())
            ->method('getHiddenCategoryIdsForCustomerGroup')
            ->with($this->identicalTo($customerGroup))
            ->willReturn($hiddenCategoryIds);

        $this->assertSame(
            $hiddenCategoryIds,
            $this->categoryVisibilityProvider->getHiddenCategoryIds($customerUser)
        );
    }

    public function testGetHiddenCategoryIdsWhenNoCustomerGroupAndCustomerGroup()
    {
        $customerUser = $this->createMock(CustomerUser::class);
        $hiddenCategoryIds = [1, 2, 3];

        $this->customerUserRelationsProvider->expects($this->once())
            ->method('getCustomer')
            ->with($this->identicalTo($customerUser))
            ->willReturn(null);
        $this->customerUserRelationsProvider->expects($this->once())
            ->method('getCustomerGroup')
            ->with($this->identicalTo($customerUser))
            ->willReturn(null);
        $this->categoryVisibilityResolver->expects($this->once())
            ->method('getHiddenCategoryIds')
            ->willReturn($hiddenCategoryIds);

        $this->assertSame(
            $hiddenCategoryIds,
            $this->categoryVisibilityProvider->getHiddenCategoryIds($customerUser)
        );
    }
}
