<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;

class CustomerTest extends EntityTestCase
{
    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $this->assertPropertyAccessors($this->createCustomerEntity(), [
            ['id', 42],
            ['name', 'Adam Weishaupt'],
            ['parent', $this->createCustomerEntity()],
            ['group', $this->createCustomerGroupEntity()],
            ['organization', $this->createOrganization()],
        ]);
    }

    /**
     * Test children
     */
    public function testChildrenCollection()
    {
        $parentCustomer = $this->createCustomerEntity();

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $parentCustomer->getChildren());
        $this->assertCount(0, $parentCustomer->getChildren());

        $customer = $this->createCustomerEntity();

        $this->assertInstanceOf(
            'OroB2B\Bundle\CustomerBundle\Entity\Customer',
            $parentCustomer->addChild($customer)
        );

        $this->assertCount(1, $parentCustomer->getChildren());

        $parentCustomer->addChild($customer);

        $this->assertCount(1, $parentCustomer->getChildren());

        $parentCustomer->removeChild($customer);

        $this->assertCount(0, $parentCustomer->getChildren());
    }

    /**
     * Test users
     */
    public function testUsersCollection()
    {
        $customer = $this->createCustomerEntity();

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $customer->getUsers());
        $this->assertCount(0, $customer->getUsers());

        $user = $this->createUserEntity();

        $customer->addUser($user);
        $this->assertEquals([$user], $customer->getUsers()->toArray());

        // entity added only once
        $customer->addUser($user);
        $this->assertEquals([$user], $customer->getUsers()->toArray());

        $customer->removeUser($user);
        $this->assertCount(0, $customer->getUsers());

        // undefined user can't be removed
        $customer->removeUser($user);
        $this->assertCount(0, $customer->getUsers());
    }

    public function testAddressesCollection()
    {
        $customer = $this->createCustomerEntity();

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $customer->getAddresses());
        $this->assertCount(0, $customer->getAddresses());

        $address = $this->createAddressEntity();

        $this->assertInstanceOf(
            'OroB2B\Bundle\CustomerBundle\Entity\Customer',
            $customer->addAddress($address)
        );
        $this->assertEquals([$address], $customer->getAddresses()->toArray());

        // entity added only once
        $customer->addAddress($address);
        $this->assertEquals([$address], $customer->getAddresses()->toArray());

        $this->assertInstanceOf(
            'OroB2B\Bundle\CustomerBundle\Entity\Customer',
            $customer->removeAddress($address)
        );
        $this->assertCount(0, $customer->getAddresses());

        // undefined user can't be removed
        $customer->removeAddress($address);
        $this->assertCount(0, $customer->getAddresses());
    }

    /**
     * @param CustomerAddress[] $addresses
     * @param string            $searchName
     * @param CustomerAddress   $expectedAddress
     * @dataProvider getAddressByTypeNameProvider
     */
    public function testGetAddressByTypeName($addresses, $searchName, $expectedAddress)
    {
        $customer = $this->createCustomerEntity();
        foreach ($addresses as $address) {
            $customer->addAddress($address);
        }

        $actualAddress = $customer->getAddressByTypeName($searchName);
        $this->assertEquals($expectedAddress, $actualAddress);
    }

    public function getAddressByTypeNameProvider()
    {
        $billingType = new AddressType(AddressType::TYPE_BILLING);
        $shippingType = new AddressType(AddressType::TYPE_SHIPPING);

        $addressWithBilling = $this->createAddressEntity();
        $addressWithBilling->addType($billingType);

        $addressWithShipping = $this->createAddressEntity();
        $addressWithShipping->addType($shippingType);

        $addressWithShippingAndBilling = $this->createAddressEntity();
        $addressWithShippingAndBilling->addType($shippingType);
        $addressWithShippingAndBilling->addType($billingType);

        return [
            'not found address with type (empty addresses)' => [
                'addresses' => [],
                'searchName' => AddressType::TYPE_BILLING,
                'expectedAddress' => null
            ],
            'not found address with type (some address exists)' => [
                'addresses' => [$addressWithShipping],
                'searchName' => AddressType::TYPE_BILLING,
                'expectedAddress' => null
            ],
            'find address by shipping name' => [
                'addresses' => [$addressWithShipping],
                'searchName' => AddressType::TYPE_SHIPPING,
                'expectedAddress' => $addressWithShipping
            ],
            'find first address by shipping name' => [
                'addresses' => [$addressWithShippingAndBilling, $addressWithShipping],
                'searchName' => AddressType::TYPE_SHIPPING,
                'expectedAddress' => $addressWithShippingAndBilling
            ],
        ];
    }

    /**
     * @param $addresses
     * @param $expectedAddress
     * @dataProvider getPrimaryAddressProvider
     */
    public function testGetPrimaryAddress($addresses, $expectedAddress)
    {
        $customer = $this->createCustomerEntity();
        foreach ($addresses as $address) {
            $customer->addAddress($address);
        }

        $this->assertEquals($expectedAddress, $customer->getPrimaryAddress());
    }

    public function getPrimaryAddressProvider()
    {
        $primaryAddress = $this->createAddressEntity();
        $primaryAddress->setPrimary(true);

        $notPrimaryAddress = $this->createAddressEntity();

        return [
            'without primary address' => [
                'addresses' => [$notPrimaryAddress],
                'expectedAddress' => null
            ],
            'one primary address' => [
                'addresses' => [$primaryAddress],
                'expectedAddress' => $primaryAddress
            ],
            'get one primary by few address' => [
                'addresses' => [$primaryAddress, $notPrimaryAddress],
                'expectedAddress' => $primaryAddress
            ],
        ];
    }

    /**
     * @return CustomerGroup
     */
    protected function createCustomerGroupEntity()
    {
        return new CustomerGroup();
    }

    /**
     * @return Customer
     */
    protected function createCustomerEntity()
    {
        return new Customer();
    }

    /**
     * @return AccountUser
     */
    protected function createUserEntity()
    {
        return new AccountUser();
    }

    /**
     * @return Organization
     */
    protected function createOrganization()
    {
        return new Organization();
    }

    protected function createAddressEntity()
    {
        return new CustomerAddress();
    }
}
