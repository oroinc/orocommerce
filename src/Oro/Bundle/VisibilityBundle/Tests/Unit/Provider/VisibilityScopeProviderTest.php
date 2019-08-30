<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Provider\ScopeCustomerCriteriaProvider;
use Oro\Bundle\CustomerBundle\Provider\ScopeCustomerGroupCriteriaProvider;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityScopeProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class VisibilityScopeProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeManager;

    /**
     * @var Website
     */
    private $website;

    /**
     * @var VisibilityScopeProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->website = $this->createMock(Website::class);

        $this->provider = new VisibilityScopeProvider($this->scopeManager);
    }

    public function testGetProductVisibilityScope()
    {
        $this->scopeManager
            ->expects($this->once())
            ->method('findOrCreate')
            ->with(ProductVisibility::getScopeType(), null);

        $this->provider->getProductVisibilityScope($this->website);
    }

    public function testGetCustomerProductVisibilityScope()
    {
        $customer = $this->createMock(Customer::class);
        $this->scopeManager
            ->expects($this->once())
            ->method('findOrCreate')
            ->with(
                CustomerProductVisibility::getScopeType(),
                [ScopeCustomerCriteriaProvider::CUSTOMER => $customer]
            );

        $this->provider->getCustomerProductVisibilityScope($customer, $this->website);
    }

    public function testGetCustomerGroupProductVisibilityScope()
    {
        $customerGroup = $this->createMock(CustomerGroup::class);
        $this->scopeManager
            ->expects($this->once())
            ->method('findOrCreate')
            ->with(
                CustomerGroupProductVisibility::getScopeType(),
                [ScopeCustomerGroupCriteriaProvider::CUSTOMER_GROUP => $customerGroup]
            );

        $this->provider->getCustomerGroupProductVisibilityScope($customerGroup, $this->website);
    }
}
