<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Entity;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\UserBundle\Tests\Unit\Entity\AbstractUserTest;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;

class AccountUserTest extends AbstractUserTest
{
    /**
     * @return AccountUser
     */
    public function getUser()
    {
        return new AccountUser();
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
        $user = $this->getUser();
        $user->setFirstName('John')
            ->setLastName('Doe');
        $this->assertEmpty($user->getCustomer());

        // createCustomer is triggered on prePersist event
        $user->createCustomer();
        $customer = $user->getCustomer();
        $this->assertInstanceOf('OroB2B\Bundle\CustomerBundle\Entity\Customer', $customer);
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

    public function testGetDefaultRole()
    {
        $this->assertEquals(AccountUser::ROLE_BUYER, $this->getUser()->getDefaultRole());
    }
}
