<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Provider\ScopeCustomerCriteriaProvider;
use Oro\Bundle\CustomerBundle\Provider\ScopeCustomerGroupCriteriaProvider;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityScopeProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class VisibilityScopeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeManager;

    /** @var VisibilityScopeProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->scopeManager = $this->createMock(ScopeManager::class);

        $this->provider = new VisibilityScopeProvider($this->scopeManager);
    }

    public function testGetProductVisibilityScope()
    {
        $website = $this->createMock(Website::class);
        $scope = $this->createMock(Scope::class);

        $this->scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with(ProductVisibility::getScopeType(), null)
            ->willReturn($scope);

        $this->assertSame(
            $scope,
            $this->provider->getProductVisibilityScope($website)
        );
    }

    public function testFindProductVisibilityScope()
    {
        $website = $this->createMock(Website::class);
        $scope = $this->createMock(Scope::class);

        $this->scopeManager->expects($this->once())
            ->method('find')
            ->with(ProductVisibility::getScopeType(), null)
            ->willReturn($scope);

        $this->assertSame(
            $scope,
            $this->provider->findProductVisibilityScope($website)
        );
    }

    public function testFindProductVisibilityScopeWhenScopeNotFound()
    {
        $website = $this->createMock(Website::class);

        $this->scopeManager->expects($this->once())
            ->method('find')
            ->with(ProductVisibility::getScopeType(), null)
            ->willReturn(null);

        $this->assertNull(
            $this->provider->findProductVisibilityScope($website)
        );
    }

    public function testFindProductVisibilityScopeId()
    {
        $website = $this->createMock(Website::class);
        $scopeId = 1;

        $this->scopeManager->expects($this->once())
            ->method('findId')
            ->with(ProductVisibility::getScopeType(), null)
            ->willReturn($scopeId);

        $this->assertSame(
            $scopeId,
            $this->provider->findProductVisibilityScopeId($website)
        );
    }

    public function testFindProductVisibilityScopeIdWhenScopeNotFound()
    {
        $website = $this->createMock(Website::class);

        $this->scopeManager->expects($this->once())
            ->method('findId')
            ->with(ProductVisibility::getScopeType(), null)
            ->willReturn(null);

        $this->assertNull(
            $this->provider->findProductVisibilityScopeId($website)
        );
    }

    public function testGetCustomerProductVisibilityScope()
    {
        $customer = $this->createMock(Customer::class);
        $website = $this->createMock(Website::class);
        $scope = $this->createMock(Scope::class);

        $this->scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with(
                CustomerProductVisibility::getScopeType(),
                [ScopeCustomerCriteriaProvider::CUSTOMER => $customer]
            )
            ->willReturn($scope);

        $this->assertSame(
            $scope,
            $this->provider->getCustomerProductVisibilityScope($customer, $website)
        );
    }

    public function testFindCustomerProductVisibilityScope()
    {
        $customer = $this->createMock(Customer::class);
        $website = $this->createMock(Website::class);
        $scope = $this->createMock(Scope::class);

        $this->scopeManager->expects($this->once())
            ->method('find')
            ->with(
                CustomerProductVisibility::getScopeType(),
                [ScopeCustomerCriteriaProvider::CUSTOMER => $customer]
            )
            ->willReturn($scope);

        $this->assertSame(
            $scope,
            $this->provider->findCustomerProductVisibilityScope($customer, $website)
        );
    }

    public function testFindCustomerProductVisibilityScopeWhenScopeNotFound()
    {
        $customer = $this->createMock(Customer::class);
        $website = $this->createMock(Website::class);

        $this->scopeManager->expects($this->once())
            ->method('find')
            ->with(
                CustomerProductVisibility::getScopeType(),
                [ScopeCustomerCriteriaProvider::CUSTOMER => $customer]
            )
            ->willReturn(null);

        $this->assertNull(
            $this->provider->findCustomerProductVisibilityScope($customer, $website)
        );
    }

    public function testFindCustomerProductVisibilityScopeId()
    {
        $customer = $this->createMock(Customer::class);
        $website = $this->createMock(Website::class);
        $scopeId = 1;

        $this->scopeManager->expects($this->once())
            ->method('findId')
            ->with(
                CustomerProductVisibility::getScopeType(),
                [ScopeCustomerCriteriaProvider::CUSTOMER => $customer]
            )
            ->willReturn($scopeId);

        $this->assertSame(
            $scopeId,
            $this->provider->findCustomerProductVisibilityScopeId($customer, $website)
        );
    }

    public function testFindCustomerProductVisibilityScopeIdWhenScopeNotFound()
    {
        $customer = $this->createMock(Customer::class);
        $website = $this->createMock(Website::class);

        $this->scopeManager->expects($this->once())
            ->method('findId')
            ->with(
                CustomerProductVisibility::getScopeType(),
                [ScopeCustomerCriteriaProvider::CUSTOMER => $customer]
            )
            ->willReturn(null);

        $this->assertNull(
            $this->provider->findCustomerProductVisibilityScopeId($customer, $website)
        );
    }

    public function testGetCustomerGroupProductVisibilityScope()
    {
        $customerGroup = $this->createMock(CustomerGroup::class);
        $website = $this->createMock(Website::class);
        $scope = $this->createMock(Scope::class);

        $this->scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with(
                CustomerGroupProductVisibility::getScopeType(),
                [ScopeCustomerGroupCriteriaProvider::CUSTOMER_GROUP => $customerGroup]
            )
            ->willReturn($scope);

        $this->assertSame(
            $scope,
            $this->provider->getCustomerGroupProductVisibilityScope($customerGroup, $website)
        );
    }

    public function testFindCustomerGroupProductVisibilityScope()
    {
        $customerGroup = $this->createMock(CustomerGroup::class);
        $website = $this->createMock(Website::class);
        $scope = $this->createMock(Scope::class);

        $this->scopeManager->expects($this->once())
            ->method('find')
            ->with(
                CustomerGroupProductVisibility::getScopeType(),
                [ScopeCustomerGroupCriteriaProvider::CUSTOMER_GROUP => $customerGroup]
            )
            ->willReturn($scope);

        $this->assertSame(
            $scope,
            $this->provider->findCustomerGroupProductVisibilityScope($customerGroup, $website)
        );
    }

    public function testFindCustomerGroupProductVisibilityScopeWhenScopeNotFound()
    {
        $customerGroup = $this->createMock(CustomerGroup::class);
        $website = $this->createMock(Website::class);

        $this->scopeManager->expects($this->once())
            ->method('find')
            ->with(
                CustomerGroupProductVisibility::getScopeType(),
                [ScopeCustomerGroupCriteriaProvider::CUSTOMER_GROUP => $customerGroup]
            )
            ->willReturn(null);

        $this->assertNull(
            $this->provider->findCustomerGroupProductVisibilityScope($customerGroup, $website)
        );
    }

    public function testFindCustomerGroupProductVisibilityScopeId()
    {
        $customerGroup = $this->createMock(CustomerGroup::class);
        $website = $this->createMock(Website::class);
        $scopeId = 1;

        $this->scopeManager->expects($this->once())
            ->method('findId')
            ->with(
                CustomerGroupProductVisibility::getScopeType(),
                [ScopeCustomerGroupCriteriaProvider::CUSTOMER_GROUP => $customerGroup]
            )
            ->willReturn($scopeId);

        $this->assertSame(
            $scopeId,
            $this->provider->findCustomerGroupProductVisibilityScopeId($customerGroup, $website)
        );
    }

    public function testFindCustomerGroupProductVisibilityScopeIdWhenScopeNotFound()
    {
        $customerGroup = $this->createMock(CustomerGroup::class);
        $website = $this->createMock(Website::class);

        $this->scopeManager->expects($this->once())
            ->method('findId')
            ->with(
                CustomerGroupProductVisibility::getScopeType(),
                [ScopeCustomerGroupCriteriaProvider::CUSTOMER_GROUP => $customerGroup]
            )
            ->willReturn(null);

        $this->assertNull(
            $this->provider->findCustomerGroupProductVisibilityScopeId($customerGroup, $website)
        );
    }
}
