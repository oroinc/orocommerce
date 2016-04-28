<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\OrderBundle\Manager\OrderAddressManager;

abstract class AbstractAddressManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrderAddressManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

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
