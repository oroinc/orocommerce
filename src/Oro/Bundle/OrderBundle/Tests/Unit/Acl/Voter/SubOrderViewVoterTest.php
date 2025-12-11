<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\OrderBundle\Acl\Voter\SubOrderViewVoter;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub as Order;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class SubOrderViewVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var SubOrderViewVoter */
    private $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->voter = new SubOrderViewVoter($this->configProvider, $this->requestStack);
    }

    /**
     * @dataProvider unsupportedAttributeDataProvider
     */
    public function testAbstainOnUnsupportedAttribute(string $attribute): void
    {
        $mainOrder = new Order();
        $order = new Order();
        $order->setParent($mainOrder);

        $this->requestStack->expects($this->never())
            ->method('getCurrentRequest');

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

        $this->requestStack->expects($this->never())
            ->method('getCurrentRequest');

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, [$attribute])
        );
    }

    /**
     * @dataProvider supportedAttributeDataProvider
     */
    public function testAbstainOnUnsupportedRoute(string $attribute): void
    {
        $mainOrder = new Order();
        $order = new Order();
        $order->setParent($mainOrder);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->createRequest('oro_order_view'));

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $order, [$attribute])
        );
    }

    /**
     *  Cover BB-26613 with an empty request (backend api call)
     *
     * @dataProvider supportedAttributeDataProvider
     */
    public function testAbstainOnApiCall(string $attribute): void
    {
        $mainOrder = new Order();
        $order = new Order();
        $order->setParent($mainOrder);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $order, [$attribute])
        );
    }

    /**
     * @dataProvider supportedAttributeDataProvider
     */
    public function testGrantedByConfiguration(string $attribute): void
    {
        $mainOrder = new Order();
        $order = new Order();
        $order->setParent($mainOrder);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->createRequest());

        $this->configProvider->expects($this->once())
            ->method('isShowSubordersInOrderHistoryEnabled')
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $order, [$attribute])
        );
    }

    /**
     * @dataProvider supportedAttributeDataProvider
     */
    public function testDeniedByConfiguration(string $attribute): void
    {
        $mainOrder = new Order();
        $order = new Order();
        $order->setParent($mainOrder);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->createRequest());

        $this->configProvider->expects($this->once())
            ->method('isShowSubordersInOrderHistoryEnabled')
            ->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $order, [$attribute])
        );
    }

    public function supportedAttributeDataProvider(): array
    {
        return [
            ['VIEW']
        ];
    }

    public function unsupportedAttributeDataProvider(): array
    {
        return [
            ['EDIT'],
            ['DELETE'],
            ['CREATE'],
            ['ASSIGN']
        ];
    }

    private function createRequest(string $route = 'oro_order_frontend_view'): Request
    {
        return new Request([], [], ['_route' => $route]);
    }
}
