<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;

class CustomerUserRelationsProviderTest extends \PHPUnit_Framework_TestCase
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
     * @var CustomerUserRelationsProvider
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

        $this->provider = new CustomerUserRelationsProvider(
            $this->configManager,
            $this->doctrineHelper
        );
    }

    /**
     * @dataProvider customerDataProvider
     * @param CustomerUser|null $customerUser
     * @param Customer|null $expectedCustomer
     */
    public function testGetCustomer(CustomerUser $customerUser = null, Customer $expectedCustomer = null)
    {
        $this->assertEquals($expectedCustomer, $this->provider->getCustomer($customerUser));
    }

    /**
     * @return array
     */
    public function customerDataProvider()
    {
        $customerUser = new CustomerUser();
        $customer = new Customer();
        $customerUser->setCustomer($customer);

        return [
            [
                null,
                null
            ],
            [
                $customerUser,
                $customer
            ]
        ];
    }

    /**
     * @dataProvider customerGroupDataProvider
     * @param CustomerUser|null $customerUser
     * @param CustomerGroup $expectedCustomerGroup
     */
    public function testGetCustomerGroup(CustomerUser $customerUser = null, CustomerGroup $expectedCustomerGroup = null)
    {
        $this->assertEquals($expectedCustomerGroup, $this->provider->getCustomerGroup($customerUser));
    }

    /**
     * @return array
     */
    public function customerGroupDataProvider()
    {
        $customerUser = new CustomerUser();
        $customer = new Customer();
        $customerGroup = new CustomerGroup();
        $customer->setGroup($customerGroup);
        $customerUser->setCustomer($customer);

        return [
            [
                null,
                null
            ],
            [
                $customerUser,
                $customerGroup
            ]
        ];
    }

    public function testGetCustomerGroupConfig()
    {
        $customerGroup = new CustomerGroup();
        $this->assertCustomerGroupConfigCall($customerGroup);

        $this->assertEquals($customerGroup, $this->provider->getCustomerGroup(null));
    }

    public function testGetCustomerIncludingEmptyAnonymous()
    {
        $customer = new Customer();
        $customerGroup = new CustomerGroup();
        $customerGroup->setName('test');
        $customer->setGroup($customerGroup);

        $this->assertCustomerGroupConfigCall($customerGroup);
        $this->assertEquals($customer, $this->provider->getCustomerIncludingEmpty(null));
    }

    public function testGetCustomerIncludingEmptyLogged()
    {
        $customer = new Customer();
        $customer->setName('test2');
        $customerGroup = new CustomerGroup();
        $customerGroup->setName('test2');
        $customer->setGroup($customerGroup);
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);

        $this->configManager->expects($this->never())
            ->method('get');

        $this->assertEquals($customer, $this->provider->getCustomerIncludingEmpty($customerUser));
    }

    /**
     * @param CustomerGroup $customerGroup
     */
    protected function assertCustomerGroupConfigCall(CustomerGroup $customerGroup)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_customer.anonymous_customer_group')
            ->willReturn(10);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCustomerBundle:CustomerGroup', 10)
            ->willReturn($customerGroup);
    }
}
