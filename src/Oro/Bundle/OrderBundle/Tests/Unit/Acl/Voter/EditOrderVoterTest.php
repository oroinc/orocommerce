<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Acl\Voter;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\OrderBundle\Acl\Voter\EditOrderVoter;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub as Order;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EditOrderVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EditOrderVoter */
    private $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->voter = new EditOrderVoter($this->doctrineHelper);
        $this->voter->setClassName(Order::class);
    }

    private function getInternalStatus(string $id): EnumOptionInterface
    {
        return new TestEnumValue(Order::INTERNAL_STATUS_CODE, Order::INTERNAL_STATUS_CODE . '.' . $id, $id);
    }

    /**
     * @dataProvider unsupportedAttributeDataProvider
     */
    public function testAbstainOnUnsupportedAttribute(string $attribute): void
    {
        $order = new Order();
        $order->setInternalStatus($this->getInternalStatus(OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN));

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($order, false)
            ->willReturn(1);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $order, [$attribute])
        );
    }

    /**
     * @dataProvider supportedAttributeDataProvider
     */
    public function testAbstainOnUnsupportedClass(string $attribute): void
    {
        $object = new \stdClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, [$attribute])
        );
    }

    /**
     * @dataProvider supportedAttributeDataProvider
     */
    public function testGrantedOnExistingOpenOrder(string $attribute): void
    {
        $order = new Order();
        $order->setInternalStatus($this->getInternalStatus(OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN));

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($order, false)
            ->willReturn(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(Order::class, 1)
            ->willReturn($order);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(Order::class)
            ->willReturn($em);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $order, [$attribute])
        );
    }

    /**
     * @dataProvider supportedAttributeDataProvider
     */
    public function testGrantedOnExistingOrderWithUndefinedStatus(string $attribute): void
    {
        $order = new Order();

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($order, false)
            ->willReturn(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(Order::class, 1)
            ->willReturn($order);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(Order::class)
            ->willReturn($em);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $order, [$attribute])
        );
    }

    /**
     * @dataProvider supportedAttributeDataProvider
     */
    public function testDeniedOnExistingClosedOrder(string $attribute): void
    {
        $order = new Order();
        $order->setInternalStatus($this->getInternalStatus(OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED));

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($order, false)
            ->willReturn(2);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(Order::class, 2)
            ->willReturn($order);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(Order::class)
            ->willReturn($em);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $order, [$attribute])
        );
    }

    /**
     * @dataProvider supportedAttributeDataProvider
     */
    public function testDeniedOnExistingCancelledOrder(string $attribute): void
    {
        $order = new Order();
        $order->setInternalStatus($this->getInternalStatus(OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED));

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($order, false)
            ->willReturn(2);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(Order::class, 2)
            ->willReturn($order);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(Order::class)
            ->willReturn($em);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $order, [$attribute])
        );
    }

    /**
     * @dataProvider supportedAttributeDataProvider
     */
    public function testAbstainOnNotFoundOrder(string $attribute): void
    {
        $order = new Order();
        $order->setInternalStatus($this->getInternalStatus(OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN));

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($order, false)
            ->willReturn(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(Order::class, 1)
            ->willReturn(null);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(Order::class)
            ->willReturn($em);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $order, [$attribute])
        );
    }

    public function supportedAttributeDataProvider(): array
    {
        return [
            ['EDIT']
        ];
    }

    public function unsupportedAttributeDataProvider(): array
    {
        return [
            ['VIEW'],
            ['DELETE'],
            ['CREATE'],
            ['ASSIGN']
        ];
    }
}
