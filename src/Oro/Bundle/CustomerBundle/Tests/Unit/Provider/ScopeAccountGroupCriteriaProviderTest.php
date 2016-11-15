<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Provider\AccountUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Provider\ScopeAccountGroupCriteriaProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ScopeAccountGroupCriteriaProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeAccountGroupCriteriaProvider
     */
    private $provider;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenStorage;

    /**
     * @var AccountUserRelationsProvider
     */
    protected $accountUserProvider;

    protected function setUp()
    {
        $this->tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $configManager = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();
        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();
        $this->accountUserProvider = new AccountUserRelationsProvider($configManager, $doctrineHelper);
        $this->provider = new ScopeAccountGroupCriteriaProvider($this->tokenStorage, $this->accountUserProvider);
    }

    public function testGetCriteriaForCurrentScope()
    {
        $accGroup = new AccountGroup();
        $accUser = new AccountUser();
        $account = new Account();
        $accUser->setAccount($account);
        $account->setGroup($accGroup);

        $token = $this->getMock(TokenInterface::class);
        $accUser->setAccount($account);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($accUser);

        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $actual = $this->provider->getCriteriaForCurrentScope();
        $this->assertEquals(['accountGroup' => $accGroup], $actual);
    }

    /**
     * @dataProvider contextDataProvider
     *
     * @param mixed $context
     * @param array $criteria
     */
    public function testGetCriteria($context, array $criteria)
    {
        $actual = $this->provider->getCriteriaByContext($context);
        $this->assertEquals($criteria, $actual);
    }

    /**
     * @return array
     */
    public function contextDataProvider()
    {
        $accountGroup = new AccountGroup();
        $accountGroupAware = new \stdClass();
        $accountGroupAware->accountGroup = $accountGroup;

        return [
            'array_context_with_account_group_key' => [
                'context' => ['accountGroup' => $accountGroup],
                'criteria' => ['accountGroup' => $accountGroup],
            ],
            'array_context_with_account_group_key_invalid_value' => [
                'context' => ['accountGroup' => 123],
                'criteria' => [],
            ],
            'array_context_without_account_group_key' => [
                'context' => [],
                'criteria' => [],
            ],
            'object_context_account_group_aware' => [
                'context' => $accountGroupAware,
                'criteria' => ['accountGroup' => $accountGroup],
            ],
            'object_context_not_account_group_aware' => [
                'context' => new \stdClass(),
                'criteria' => [],
            ],
        ];
    }
}
