<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Provider;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Provider\ScopeAccountCriteriaProvider;
use Oro\Bundle\CustomerBundle\Provider\ScopeAccountGroupCriteriaProvider;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityScopeProvider;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class VisibilityScopeProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
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

    public function testGetAccountProductVisibilityScope()
    {
        $account = $this->createMock(Account::class);
        $this->scopeManager
            ->expects($this->once())
            ->method('findOrCreate')
            ->with(AccountProductVisibility::getScopeType(), [
                ScopeAccountCriteriaProvider::ACCOUNT => $account
            ]);

        $this->provider->getAccountProductVisibilityScope($account, $this->website);
    }

    public function testGetAccountGroupProductVisibilityScope()
    {
        $accountGroup = $this->createMock(CustomerGroup::class);
        $this->scopeManager
            ->expects($this->once())
            ->method('findOrCreate')
            ->with(AccountGroupProductVisibility::getScopeType(), [
                ScopeAccountGroupCriteriaProvider::FIELD_NAME => $accountGroup
            ]);

        $this->provider->getAccountGroupProductVisibilityScope($accountGroup, $this->website);
    }
}
