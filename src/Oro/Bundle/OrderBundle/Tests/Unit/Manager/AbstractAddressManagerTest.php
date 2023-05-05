<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Component\Testing\ReflectionUtil;

abstract class AbstractAddressManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var OrderAddressManager */
    protected $manager;

    protected function getCustomerAddress(int $id): CustomerAddress
    {
        $address = new CustomerAddress();
        ReflectionUtil::setId($address, $id);

        return $address;
    }

    protected function getCustomerUserAddress(int $id): CustomerUserAddress
    {
        $address = new CustomerUserAddress();
        ReflectionUtil::setId($address, $id);

        return $address;
    }

    public function testGetIdentifierFailed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity with "Oro\Bundle\CustomerBundle\Entity\CustomerAddress" not registered');

        $this->manager->getIdentifier($this->getCustomerAddress(1));
    }

    public function testGetIdentifier()
    {
        $this->manager->addEntity('a', CustomerAddress::class);

        $this->assertEquals(
            'a_1',
            $this->manager->getIdentifier($this->getCustomerAddress(1))
        );
    }

    /**
     * @dataProvider identifierDataProvider
     */
    public function testGetEntityByIdentifierFailed(string $identifier, int $expectedId, array $exception = [])
    {
        if ($exception) {
            [$exception, $message] = $exception;
            $this->expectException($exception);
            $this->expectExceptionMessage($message);
        }

        $em = $this->createMock(ObjectManager::class);
        $em->expects($expectedId ? $this->atLeastOnce() : $this->never())
            ->method('find')
            ->with($this->isType('string'), $this->equalTo($expectedId));

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->manager->addEntity('au', CustomerUserAddress::class);
        $this->manager->getEntityByIdentifier($identifier);
    }

    public function identifierDataProvider(): array
    {
        return [
            'no delimiter' => ['a1', 0, [\InvalidArgumentException::class, 'Wrong identifier "a1"']],
            'not int id' => ['a_bla', 0, [\InvalidArgumentException::class, 'Wrong entity id "bla"']],
            'wrong identifier' => ['au_1_bla', 0, [\InvalidArgumentException::class, 'Wrong identifier "au_1_bla"']],
            'wrong identifier int' => ['au_1_1', 0, [\InvalidArgumentException::class, 'Wrong identifier "au_1_1"']],
            'empty alias' => ['a_1', 0, [\InvalidArgumentException::class, 'Unknown alias "a"']],
        ];
    }

    public function testGetEntityByIdentifier()
    {
        $entity = $this->getCustomerUserAddress(1);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->exactly(2))->method('find')
            ->with($this->isType('string'), $this->isType('integer'))
            ->willReturnOnConsecutiveCalls($entity, null);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->manager->addEntity('au', CustomerUserAddress::class);
        $this->assertEquals($entity, $this->manager->getEntityByIdentifier('au_1'));
        $this->assertNull($this->manager->getEntityByIdentifier('au_2'));
    }
}
