<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Provider\ScopeCustomerGroupCriteriaProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ScopeCustomerGroupCriteriaProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeCustomerGroupCriteriaProvider
     */
    private $provider;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenStorage;

    /**
     * @var CustomerUserRelationsProvider
     */
    protected $customerUserProvider;

    protected function setUp()
    {
        $this->tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $configManager = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();
        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();
        $this->customerUserProvider = new CustomerUserRelationsProvider($configManager, $doctrineHelper);
        $this->provider = new ScopeCustomerGroupCriteriaProvider($this->tokenStorage, $this->customerUserProvider);
    }

    public function testGetCriteriaForCurrentScope()
    {
        $accGroup = new CustomerGroup();
        $accUser = new CustomerUser();
        $customer = new Customer();
        $accUser->setCustomer($customer);
        $customer->setGroup($accGroup);

        $token = $this->createMock(TokenInterface::class);
        $accUser->setCustomer($customer);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($accUser);

        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $actual = $this->provider->getCriteriaForCurrentScope();
        $this->assertEquals(['customerGroup' => $accGroup], $actual);
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
        $customerGroup = new CustomerGroup();
        $customerGroupAware = new \stdClass();
        $customerGroupAware->customerGroup = $customerGroup;

        return [
            'array_context_with_customer_group_key' => [
                'context' => ['customerGroup' => $customerGroup],
                'criteria' => ['customerGroup' => $customerGroup],
            ],
            'array_context_with_customer_group_key_invalid_value' => [
                'context' => ['customerGroup' => 123],
                'criteria' => [],
            ],
            'array_context_without_customer_group_key' => [
                'context' => [],
                'criteria' => [],
            ],
            'object_context_customer_group_aware' => [
                'context' => $customerGroupAware,
                'criteria' => ['customerGroup' => $customerGroup],
            ],
            'object_context_not_customer_group_aware' => [
                'context' => new \stdClass(),
                'criteria' => [],
            ],
        ];
    }

    public function testGetCriteriaValueType()
    {
        $this->assertEquals(CustomerGroup::class, $this->provider->getCriteriaValueType());
    }
}
