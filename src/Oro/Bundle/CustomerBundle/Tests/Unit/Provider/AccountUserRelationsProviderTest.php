<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Provider\AccountUserRelationsProvider;

class AccountUserRelationsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var AccountUserRelationsProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new AccountUserRelationsProvider(
            $this->configManager,
            $this->doctrineHelper
        );
    }

    /**
     * @dataProvider accountDataProvider
     * @param AccountUser|null $accountUser
     * @param Account|null $expectedAccount
     */
    public function testGetAccount(AccountUser $accountUser = null, Account $expectedAccount = null)
    {
        $this->assertEquals($expectedAccount, $this->provider->getAccount($accountUser));
    }

    /**
     * @return array
     */
    public function accountDataProvider()
    {
        $accountUser = new AccountUser();
        $account = new Account();
        $accountUser->setAccount($account);

        return [
            [
                null,
                null
            ],
            [
                $accountUser,
                $account
            ]
        ];
    }

    /**
     * @dataProvider accountGroupDataProvider
     * @param AccountUser|null $accountUser
     * @param CustomerGroup $expectedAccountGroup
     */
    public function testGetAccountGroup(AccountUser $accountUser = null, CustomerGroup $expectedAccountGroup = null)
    {
        $this->assertEquals($expectedAccountGroup, $this->provider->getAccountGroup($accountUser));
    }

    /**
     * @return array
     */
    public function accountGroupDataProvider()
    {
        $accountUser = new AccountUser();
        $account = new Account();
        $accountGroup = new CustomerGroup();
        $account->setGroup($accountGroup);
        $accountUser->setAccount($account);

        return [
            [
                null,
                null
            ],
            [
                $accountUser,
                $accountGroup
            ]
        ];
    }

    public function testGetAccountGroupConfig()
    {
        $accountGroup = new CustomerGroup();
        $this->assertAccountGroupConfigCall($accountGroup);

        $this->assertEquals($accountGroup, $this->provider->getAccountGroup(null));
    }

    public function testGetAccountIncludingEmptyAnonymous()
    {
        $account = new Account();
        $accountGroup = new CustomerGroup();
        $accountGroup->setName('test');
        $account->setGroup($accountGroup);

        $this->assertAccountGroupConfigCall($accountGroup);
        $this->assertEquals($account, $this->provider->getAccountIncludingEmpty(null));
    }

    public function testGetAccountIncludingEmptyLogged()
    {
        $account = new Account();
        $account->setName('test2');
        $accountGroup = new CustomerGroup();
        $accountGroup->setName('test2');
        $account->setGroup($accountGroup);
        $accountUser = new AccountUser();
        $accountUser->setAccount($account);

        $this->configManager->expects($this->never())
            ->method('get');

        $this->assertEquals($account, $this->provider->getAccountIncludingEmpty($accountUser));
    }

    /**
     * @param CustomerGroup $accountGroup
     */
    protected function assertAccountGroupConfigCall(CustomerGroup $accountGroup)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_customer.anonymous_account_group')
            ->willReturn(10);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCustomerBundle:CustomerGroup', 10)
            ->willReturn($accountGroup);
    }
}
