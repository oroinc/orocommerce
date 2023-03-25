<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrderBundle\Datagrid\OrderLineItemsOrderObjectAccessListener;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class OrderLineItemsOrderObjectAccessListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var BuildBefore */
    private $event;

    /** @var OrderLineItemsOrderObjectAccessListener */
    private $listener;

    protected function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $repo = $this->createMock(ObjectRepository::class);

        $parameters = new ParameterBag(['order_id' => 1]);
        $datagrid = $this->createMock(DatagridInterface::class);

        $registry->expects($this->once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(new Order());
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->event = new BuildBefore($datagrid, $this->createMock(DatagridConfiguration::class));

        $this->listener = new OrderLineItemsOrderObjectAccessListener($this->authorizationChecker, $registry);
    }

    public function testOnBuildBeforeWhenAccessGranted()
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $this->listener->onBuildBefore($this->event);
    }

    public function testOnBuildBeforeWhenAccessIsNotGranted()
    {
        $this->expectException(AccessDeniedException::class);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $this->listener->onBuildBefore($this->event);
    }
}
