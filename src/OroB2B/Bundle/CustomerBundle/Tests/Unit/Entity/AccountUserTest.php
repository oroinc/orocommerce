<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Entity;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Tests\Unit\Entity\AbstractUserTest;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AccountUserTest extends AbstractUserTest
{
    /**
     * @return AccountUser
     */
    public function getUser()
    {
        return new AccountUser();
    }

    public function createAddressEntity()
    {
        return new AccountUserAddress();
    }

    public function testProperties()
    {
        $customer = new Customer();

        $user = $this->getUser();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('test@example.com');
        $user->setCustomer($customer);

        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('test@example.com', $user->getUsername());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals($customer, $user->getCustomer());
    }

    public function testCreateCustomer()
    {
        $organization = new Organization();
        $organization->setName('test');

        $user = $this->getUser();
        $user->setOrganization($organization)
            ->setFirstName('John')
            ->setLastName('Doe');
        $this->assertEmpty($user->getCustomer());

        // createCustomer is triggered on prePersist event
        $user->createCustomer();
        $customer = $user->getCustomer();
        $this->assertInstanceOf('OroB2B\Bundle\CustomerBundle\Entity\Customer', $customer);
        $this->assertEquals($organization, $customer->getOrganization());
        $this->assertEquals('John Doe', $customer->getName());

        // new customer created only if it not defined
        $user->setFirstName('Jane');
        $user->createCustomer();
        $this->assertEquals('John Doe', $user->getCustomer()->getName());
    }

    public function testSerializing()
    {
        $user = $this->getUser();
        $data = $user->serialize();

        $this->assertNotEmpty($data);

        $user
            ->setPassword('new-pass')
            ->setConfirmationToken('token')
            ->setUsername('new-name');

        $user->unserialize($data);

        $this->assertEmpty($user->getPassword());
        $this->assertEmpty($user->getConfirmationToken());
        $this->assertEmpty($user->getUsername());
        $this->assertEquals('new-name', $user->getEmail());
    }

    /**
     * @return array
     */
    public function provider()
    {
        return [
            ['username', 'test'],
            ['email', 'test'],
            ['nameprefix', 'test'],
            ['firstname', 'test'],
            ['middlename', 'test'],
            ['lastname', 'test'],
            ['namesuffix', 'test'],
            ['birthday', new \DateTime()],
            ['password', 'test'],
            ['plainPassword', 'test'],
            ['confirmationToken', 'test'],
            ['passwordRequestedAt', new \DateTime()],
            ['passwordChangedAt', new \DateTime()],
            ['lastLogin', new \DateTime()],
            ['loginCount', 11],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['salt', md5('user')]
        ];
    }

    public function testPrePersist()
    {
        $user = $this->getUser();
        $user->prePersist();
        $this->assertInstanceOf('\DateTime', $user->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $user->getUpdatedAt());
        $this->assertEquals(0, $user->getLoginCount());
    }

    public function testPreUpdateUnChanged()
    {
        $changeSet = [
            'lastLogin' => null,
            'loginCount' => null
        ];

        $user = $this->getUser();
        $updatedAt = new \DateTime('2015-01-01');
        $user->setUpdatedAt($updatedAt);

        /** @var \PHPUnit_Framework_MockObject_MockObject|PreUpdateEventArgs $event */
        $event = $this->getMockBuilder('Doctrine\ORM\Event\PreUpdateEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getEntityChangeSet')
            ->will($this->returnValue($changeSet));

        $user->preUpdate($event);
        $this->assertEquals($updatedAt, $user->getUpdatedAt());
    }

    public function testPreUpdateChanged()
    {
        $changeSet = ['lastname' => null];

        $user = $this->getUser();
        $updatedAt = new \DateTime('2015-01-01');
        $user->setUpdatedAt($updatedAt);

        /** @var \PHPUnit_Framework_MockObject_MockObject|PreUpdateEventArgs $event */
        $event = $this->getMockBuilder('Doctrine\ORM\Event\PreUpdateEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getEntityChangeSet')
            ->will($this->returnValue($changeSet));

        $user->preUpdate($event);
        $this->assertNotEquals($updatedAt, $user->getUpdatedAt());
    }

    public function testUnserialize()
    {
        $user = $this->getUser();
        $serialized = [
            'password',
            'salt',
            'username',
            true,
            false,
            'confirmation_token',
            10
        ];
        $user->unserialize(serialize($serialized));

        $this->assertEquals($serialized[0], $user->getPassword());
        $this->assertEquals($serialized[1], $user->getSalt());
        $this->assertEquals($serialized[2], $user->getUsername());
        $this->assertEquals($serialized[3], $user->isEnabled());
        $this->assertEquals($serialized[4], $user->isConfirmed());
        $this->assertEquals($serialized[5], $user->getConfirmationToken());
        $this->assertEquals($serialized[6], $user->getId());
    }

    public function testIsEnabledAndIsConfirmed()
    {
        $user = $this->getUser();

        $this->assertTrue($user->isEnabled());
        $this->assertTrue($user->isConfirmed());
        $this->assertTrue($user->isAccountNonExpired());
        $this->assertTrue($user->isAccountNonLocked());

        $user->setEnabled(false);

        $this->assertFalse($user->isEnabled());
        $this->assertFalse($user->isAccountNonLocked());

        $user->setEnabled(true);
        $user->setConfirmed(false);

        $this->assertFalse($user->isConfirmed());
        $this->assertFalse($user->isAccountNonLocked());
    }

    public function testAddressesCollection()
    {
        $accountUser = $this->getUser();
        static::assertPropertyCollections($accountUser, [['addresses', $this->createAddressEntity()]]);
    }

    /**
     * @param AccountUserAddress[] $addresses
     * @param string            $searchName
     * @param AccountUserAddress   $expectedAddress
     * @dataProvider getAddressByTypeNameProvider
     */
    public function testGetAddressByTypeName($addresses, $searchName, $expectedAddress)
    {
        $accountUser = $this->getUser();
        foreach ($addresses as $address) {
            $accountUser->addAddress($address);
        }

        $actualAddress = $accountUser->getAddressByTypeName($searchName);
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
        $accountUser = $this->getUser();
        foreach ($addresses as $address) {
            $accountUser->addAddress($address);
        }

        $this->assertEquals($expectedAddress, $accountUser->getPrimaryAddress());
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
}
