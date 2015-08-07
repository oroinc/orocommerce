<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Model\OrderAddressManager;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressProvider;

class OrderAddressManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrderAddressManager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OrderAddressProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->provider = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Provider\OrderAddressProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->manager = new OrderAddressManager(
            $this->provider,
            $this->registry,
            'OroB2B\Bundle\OrderBundle\Entity\OrderAddress'
        );
    }

    protected function tearDown()
    {
        unset($this->manager, $this->provider, $this->registry);
    }

    /**
     * @param AbstractAddress $abstractAddress
     * @param OrderAddress|null $expected
     * @param OrderAddress|null $orderAddress
     *
     * @dataProvider orderDataProvider
     */
    public function testUpdateFromAbstract(
        AbstractAddress $abstractAddress,
        OrderAddress $expected = null,
        OrderAddress $orderAddress = null
    ) {
        $classMetadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $classMetadata->expects($this->once())->method('getFieldNames')->willReturn(['street', 'city', 'label']);
        $classMetadata->expects($this->once())->method('getAssociationNames')->willReturn(['country', 'region']);

        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $em->expects($this->once())->method('getClassMetadata')->willReturn($classMetadata);

        $this->registry->expects($this->any())->method('getManagerForClass')->with($this->isType('string'))
            ->willReturn($em);

        $orderAddress = $this->manager->updateFromAbstract($abstractAddress, $orderAddress);
        $this->assertEquals($expected, $orderAddress);
    }

    /**
     * @return array
     */
    public function orderDataProvider()
    {
        $country = new Country('US');
        $region = new Region('US-AL');

        return [
            'empty account address' => [new AccountAddress(), new OrderAddress()],
            'empty account user address' => [new AccountUserAddress(), new OrderAddress()],
            'from account address' => [
                (new AccountAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                (new OrderAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
            ],
            'from account user address' => [
                (new AccountUserAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                (new OrderAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
            ],
            'do not override value from existing with empty one' => [
                (new AccountUserAddress())
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                (new OrderAddress())
                    ->setLabel('ExistingLabel')
                    ->setCountry($country)
                    ->setRegion($region)
                    ->setStreet('Street')
                    ->setCity('City'),
                (new OrderAddress())->setLabel('ExistingLabel'),
            ],
        ];
    }

    /**
     * @param Order $order
     * @param array $accountAddresses
     * @param array $accountUserAddresses
     * @param array $expected
     *
     * @dataProvider groupedAddressDataProvider
     */
    public function testGetGroupedAddresses(
        Order $order,
        array $accountAddresses = [],
        array $accountUserAddresses = [],
        array $expected = []
    ) {
        $this->provider->expects($this->any())->method('getAccountAddresses')->willReturn($accountAddresses);
        $this->provider->expects($this->any())->method('getAccountUserAddresses')->willReturn($accountUserAddresses);

        $this->manager->addEntity('au', 'OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress');
        $this->manager->addEntity('a', 'OroB2B\Bundle\AccountBundle\Entity\AccountAddress');

        $this->assertEquals($expected, $this->manager->getGroupedAddresses($order, AddressType::TYPE_BILLING));
    }

    /**
     * @return array
     */
    public function groupedAddressDataProvider()
    {
        return [
            'empty account user' => [new Order()],
            'empty account' => [
                (new Order())->setAccountUser(new AccountUser()),
                [],
                [
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress', 1),
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress', 2),
                ],
                [
                    'orob2b.account.accountuser.entity_label' => [
                        'au_1' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress',
                            1
                        ),
                        'au_2' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress',
                            2
                        ),
                    ],
                ],
            ],
            'account' => [
                (new Order())->setAccountUser(new AccountUser())->setAccount(new Account()),
                [
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountAddress', 1),
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountAddress', 2),
                ],
                [],
                [
                    'orob2b.account.entity_label' => [
                        'a_1' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountAddress',
                            1
                        ),
                        'a_2' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountAddress',
                            2
                        ),
                    ],
                ],
            ],
            'full' => [
                (new Order())->setAccountUser(new AccountUser())->setAccount(new Account()),
                [
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountAddress', 1),
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountAddress', 2),
                ],
                [
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress', 1),
                    $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress', 2),
                ],
                [
                    'orob2b.account.entity_label' => [
                        'a_1' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountAddress',
                            1
                        ),
                        'a_2' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountAddress',
                            2
                        ),
                    ],
                    'orob2b.account.accountuser.entity_label' => [
                        'au_1' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress',
                            1
                        ),
                        'au_2' => $this->getEntity(
                            'OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress',
                            2
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Entity with "OroB2B\Bundle\AccountBundle\Entity\AccountAddress" not registered
     */
    public function testGetIdentifierFailed()
    {
        $this->manager->getIdentifier($this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountAddress', 1));
    }

    public function testGetIdentifier()
    {
        $this->manager->addEntity('a', 'OroB2B\Bundle\AccountBundle\Entity\AccountAddress');

        $this->assertEquals(
            'a_1',
            $this->manager->getIdentifier($this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountAddress', 1))
        );
    }

    /**
     * @expectedException
     * @expectedExceptionMessage
     *
     * @dataProvider identifierDataProvider
     * @param string $identifier
     * @param int $expectedId
     * @param array $exception
     */
    public function testGetEntityByIdentifierFailed($identifier, $expectedId, array $exception = [])
    {
        if ($exception) {
            list ($exception, $message) = $exception;
            $this->setExpectedException($exception, $message);
        }

        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $em->expects($expectedId ? $this->atLeastOnce() : $this->never())->method('find')
            ->with($this->isType('string'), $this->equalTo($expectedId));

        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($em);

        $this->manager->addEntity('au', 'OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress');
        $this->manager->getEntityByIdentifier($identifier);
    }

    /**
     * @return array
     */
    public function identifierDataProvider()
    {
        return [
            'no delimiter' => ['a1', 0, ['\InvalidArgumentException', 'Wrong identifier "a1"']],
            'not int id' => ['a_bla', 0, ['\InvalidArgumentException', 'Wrong entity id "bla"']],
            'wrong identifier' => ['au_1_bla', 0, ['\InvalidArgumentException', 'Wrong identifier "au_1_bla"']],
            'wrong identifier int' => ['au_1_1', 0, ['\InvalidArgumentException', 'Wrong identifier "au_1_1"']],
            'empty alias' => ['a_1', 0, ['\InvalidArgumentException', 'Unknown alias "a"']],
        ];
    }

    public function testGetEntityByIdentifier()
    {
        $entity = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress', 1);

        $em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $em->expects($this->exactly(2))->method('find')
            ->with($this->isType('string'), $this->isType('integer'))
            ->will($this->onConsecutiveCalls($entity, null));

        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($em);

        $this->manager->addEntity('au', 'OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress');
        $this->assertEquals($entity, $this->manager->getEntityByIdentifier('au_1'));
        $this->assertNull($this->manager->getEntityByIdentifier('au_2'));
    }

    /**
     * @param string $className
     * @param int $id
     * @return AbstractAddress
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }
}
