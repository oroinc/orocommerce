<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Provider\AccountUserRelationsProvider;

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
     * @param AccountGroup $expectedAccountGroup
     */
    public function testGetAccountGroup(AccountUser $accountUser = null, AccountGroup $expectedAccountGroup = null)
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
        $accountGroup = new AccountGroup();
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
        $accountGroup = new AccountGroup();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_account.anonymous_account_group')
            ->willReturn(10);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BAccountBundle:AccountGroup', 10)
            ->willReturn($accountGroup);

        $this->assertEquals($accountGroup, $this->provider->getAccountGroup(null));
    }
}
