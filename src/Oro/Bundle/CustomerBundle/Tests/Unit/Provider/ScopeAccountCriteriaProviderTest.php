<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Provider;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Provider\ScopeAccountCriteriaProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ScopeAccountCriteriaProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeAccountCriteriaProvider
     */
    private $provider;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenStorage;

    protected function setUp()
    {
        $this->tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $this->provider = new ScopeAccountCriteriaProvider($this->tokenStorage);
    }

    public function testGetCriteriaForCurrentScope()
    {
        $accUser = new AccountUser();
        $account = new Account();
        $accUser->setAccount($account);
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($accUser);
        $actual = $this->provider->getCriteriaForCurrentScope();
        $this->assertEquals(['account' => $account], $actual);
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
        $account = new Account();
        $accountAware = new \stdClass();
        $accountAware->account = $account;

        return [
            'array_context_with_account_key' => [
                'context' => ['account' => $account],
                'criteria' => ['account' => $account],
            ],
            'array_context_with_account_key_invalid_value' => [
                'context' => ['account' => 123],
                'criteria' => [],
            ],
            'array_context_without_account_key' => [
                'context' => [],
                'criteria' => [],
            ],
            'object_context_account_aware' => [
                'context' => $accountAware,
                'criteria' => ['account' => $account],
            ],
            'object_context_not_account_aware' => [
                'context' => new \stdClass(),
                'criteria' => [],
            ],
        ];
    }
}
