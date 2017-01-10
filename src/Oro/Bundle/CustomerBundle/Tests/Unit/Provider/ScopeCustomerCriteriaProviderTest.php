<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\ScopeCustomerCriteriaProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ScopeCustomerCriteriaProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeCustomerCriteriaProvider
     */
    private $provider;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenStorage;

    protected function setUp()
    {
        $this->tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $this->provider = new ScopeCustomerCriteriaProvider($this->tokenStorage);
    }

    public function testGetCriteriaForCurrentScope()
    {
        $accUser = new CustomerUser();
        $customer = new Customer();

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
        $this->assertEquals(['customer' => $customer], $actual);
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
        $customer = new Customer();
        $customerAware = new \stdClass();
        $customerAware->customer = $customer;

        return [
            'array_context_with_customer_key' => [
                'context' => ['customer' => $customer],
                'criteria' => ['customer' => $customer],
            ],
            'array_context_with_customer_key_invalid_value' => [
                'context' => ['customer' => 123],
                'criteria' => [],
            ],
            'array_context_without_customer_key' => [
                'context' => [],
                'criteria' => [],
            ],
            'object_context_customer_aware' => [
                'context' => $customerAware,
                'criteria' => ['customer' => $customer],
            ],
            'object_context_not_customer_aware' => [
                'context' => new \stdClass(),
                'criteria' => [],
            ],
        ];
    }

    public function testGetCriteriaValueType()
    {
        $this->assertEquals(Customer::class, $this->provider->getCriteriaValueType());
    }
}
