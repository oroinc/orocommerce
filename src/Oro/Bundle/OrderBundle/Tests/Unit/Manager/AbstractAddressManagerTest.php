<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;

abstract class AbstractAddressManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderAddressManager */
    protected $manager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    protected $registry;

    public function testGetIdentifierFailed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity with "Oro\Bundle\CustomerBundle\Entity\CustomerAddress" not registered');

        $this->manager->getIdentifier($this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerAddress', 1));
    }

    public function testGetIdentifier()
    {
        $this->manager->addEntity('a', 'Oro\Bundle\CustomerBundle\Entity\CustomerAddress');

        $this->assertEquals(
            'a_1',
            $this->manager->getIdentifier($this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerAddress', 1))
        );
    }

    /**
     * @dataProvider identifierDataProvider
     * @param string $identifier
     * @param int $expectedId
     * @param array $exception
     */
    public function testGetEntityByIdentifierFailed($identifier, $expectedId, array $exception = [])
    {
        if ($exception) {
            [$exception, $message] = $exception;
            $this->expectException($exception);
            $this->expectExceptionMessage($message);
        }

        $em = $this->createMock('Doctrine\Persistence\ObjectManager');
        $em->expects($expectedId ? $this->atLeastOnce() : $this->never())->method('find')
            ->with($this->isType('string'), $this->equalTo($expectedId));

        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($em);

        $this->manager->addEntity('au', 'Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress');
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
        $entity = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress', 1);

        $em = $this->createMock('Doctrine\Persistence\ObjectManager');
        $em->expects($this->exactly(2))->method('find')
            ->with($this->isType('string'), $this->isType('integer'))
            ->will($this->onConsecutiveCalls($entity, null));

        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($em);

        $this->manager->addEntity('au', 'Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress');
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
