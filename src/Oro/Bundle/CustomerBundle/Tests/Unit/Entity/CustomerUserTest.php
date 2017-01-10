<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Unit\Entity\AbstractUserTest;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\CustomerBundle\Tests\Unit\Traits\AddressEntityTestTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CustomerUserTest extends AbstractUserTest
{
    use AddressEntityTestTrait;

    /**
     * @return CustomerUser
     */
    public function getUser()
    {
        return new CustomerUser();
    }

    /**
     * @return CustomerUserAddress
     */
    public function createAddressEntity()
    {
        return new CustomerUserAddress();
    }

    /**
     * @return CustomerUser
     */
    protected function createTestedEntity()
    {
        return $this->getUser();
    }

    public function testCollections()
    {
        $this->assertPropertyCollections(new CustomerUser(), [
            ['addresses', $this->createAddressEntity()],
            ['salesRepresentatives', new User()],
        ]);
    }

    public function testCreateCustomer()
    {
        $organization = new Organization();
        $organization->setName('test');

        $user = $this->getUser();
        $user->setOrganization($organization)
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setOwner(new User());
        $this->assertEmpty($user->getCustomer());
        $address = new CustomerAddress();
        $user->addAddress($address);
        $this->assertContains($address, $user->getAddresses());
        $backendUser = new User();
        $user->setOwner($backendUser);
        $this->assertEquals($user->getOwner(), $backendUser);

        // createCustomer is triggered on prePersist event
        $user->createCustomer();
        $customer = $user->getCustomer();
        $this->assertInstanceOf('Oro\Bundle\CustomerBundle\Entity\Customer', $customer);
        $this->assertEquals($organization, $customer->getOrganization());
        $this->assertEquals('John Doe', $customer->getName());

        // new customer created only if it not defined
        $user->setFirstName('Jane');
        $user->createCustomer();
        $this->assertEquals('John Doe', $user->getCustomer()->getName());

        //Creating an customer with company name parameter instead of use first and last name
        $user->setCustomer(null);
        $user->createCustomer('test company');
        $this->assertEquals('test company', $user->getCustomer()->getName());
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
            ['customer', new Customer()],
            ['username', 'test'],
            ['email', 'test'],
            ['nameprefix', 'test'],
            ['firstName', 'test'],
            ['middleName', 'test'],
            ['lastName', 'test'],
            ['nameSuffix', 'test'],
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
            ['website', new Website()],
            ['salt', md5('user')],
        ];
    }

    public function testPrePersist()
    {
        $user = $this->getUser();
        $user->prePersist();
        $this->assertInstanceOf('\DateTime', $user->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $user->getUpdatedAt());
        $this->assertEquals(0, $user->getLoginCount());
        $this->assertNotEmpty($user->getCustomer());
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
        $this->assertTrue($user->isCustomerNonLocked());

        $user->setEnabled(false);

        $this->assertFalse($user->isEnabled());
        $this->assertFalse($user->isCustomerNonLocked());

        $user->setEnabled(true);
        $user->setConfirmed(false);

        $this->assertFalse($user->isConfirmed());
        $this->assertFalse($user->isCustomerNonLocked());
    }

    public function testGetFullName()
    {
        $user = $this->getUser();
        $user
            ->setFirstName('FirstName')
            ->setLastName('LastName');

        $this->assertSame('FirstName LastName', $user->getFullName());
    }

    public function testSettingsAccessors()
    {
        $user = $this->getUser();
        $website = new Website();

        $user->setWebsiteSettings(new CustomerUserSettings(new Website()))
            ->setWebsiteSettings(new CustomerUserSettings($website))
            ->setWebsiteSettings((new CustomerUserSettings($website))->setCurrency('USD'))
            ->setWebsiteSettings(new CustomerUserSettings(new Website()));

        $this->assertSame('USD', $user->getWebsiteSettings($website)->getCurrency());
    }
}
