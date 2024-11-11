<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Manager\AbstractAddressManager;
use Oro\Bundle\OrderBundle\Provider\AddressProviderInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

abstract class AbstractAddressManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AddressProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $addressProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var PropertyAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $propertyAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->addressProvider = $this->createMock(AddressProviderInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    abstract protected function getAddressManager(): AbstractAddressManager;

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

    public function testGetIdentifierFailed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Entity with "%s" not registered', CustomerAddress::class));

        $this->getAddressManager()->getIdentifier($this->getCustomerAddress(1));
    }

    public function testGetIdentifier(): void
    {
        $addressManager = $this->getAddressManager();
        $addressManager->addEntity('a', CustomerAddress::class);
        $addressManager->addEntity('au', CustomerUserAddress::class);

        self::assertEquals('a_1', $addressManager->getIdentifier($this->getCustomerAddress(1)));
        self::assertEquals('au_2', $addressManager->getIdentifier($this->getCustomerUserAddress(2)));
    }

    /**
     * @dataProvider getEntityByIdentifierFailedDataProvider
     */
    public function testGetEntityByIdentifierFailed(string $identifier, string $exceptionMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $addressManager = $this->getAddressManager();
        $addressManager->addEntity('au', CustomerUserAddress::class);
        $addressManager->getEntityByIdentifier($identifier);
    }

    public static function getEntityByIdentifierFailedDataProvider(): array
    {
        return [
            'no delimiter' => ['a1', 'Wrong identifier "a1"'],
            'not int id' => ['a_bla', 'Wrong entity id "bla"'],
            'wrong identifier' => ['au_1_bla', 'Wrong identifier "au_1_bla"'],
            'wrong identifier int' => ['au_1_1', 'Wrong identifier "au_1_1"'],
            'empty alias' => ['a_1', 'Unknown alias "a"'],
        ];
    }

    public function testGetEntityByIdentifier(): void
    {
        $customerAddress1 = $this->getCustomerAddress(1);
        $customerUserAddress1 = $this->getCustomerUserAddress(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(3))
            ->method('find')
            ->with(self::isType('string'), self::isType('integer'))
            ->willReturnMap([
                [CustomerAddress::class, 1, $customerAddress1],
                [CustomerUserAddress::class, 1, $customerUserAddress1],
                [CustomerUserAddress::class, 2, null]
            ]);
        $this->doctrine->expects(self::exactly(3))
            ->method('getManagerForClass')
            ->willReturn($em);

        $addressManager = $this->getAddressManager();
        $addressManager->addEntity('a', CustomerAddress::class);
        $addressManager->addEntity('au', CustomerUserAddress::class);

        self::assertSame($customerAddress1, $addressManager->getEntityByIdentifier('a_1'));
        self::assertSame($customerUserAddress1, $addressManager->getEntityByIdentifier('au_1'));
        self::assertNull($addressManager->getEntityByIdentifier('au_2'));
    }
}
