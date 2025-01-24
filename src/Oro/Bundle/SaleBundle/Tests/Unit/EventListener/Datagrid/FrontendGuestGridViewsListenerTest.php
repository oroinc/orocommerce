<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\SaleBundle\EventListener\Datagrid\FrontendGuestGridViewsListener;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FrontendGuestGridViewsListenerTest extends TestCase
{
    private FrontendGuestGridViewsListener $listener;
    private TokenStorageInterface|MockObject $tokenStorage;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->listener = new FrontendGuestGridViewsListener($this->tokenStorage);
    }

    public function testOnPreBuildWithAnonymousCustomerUserToken(): void
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $event = $this->createMock(PreBuild::class);

        $parameters = new ParameterBag();

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $event->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->listener->onPreBuild($event);

        $this->assertTrue($parameters->has(GridViewsExtension::GRID_VIEW_ROOT_PARAM));
        $this->assertEquals(
            [GridViewsExtension::DISABLED_PARAM => true],
            $parameters->get(GridViewsExtension::GRID_VIEW_ROOT_PARAM)
        );
    }

    public function testOnPreBuildWithNonAnonymousCustomerUserToken(): void
    {
        $token = $this->createMock(UsernamePasswordOrganizationToken::class);
        $event = $this->createMock(PreBuild::class);

        $parameters = new ParameterBag();

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $event->expects(self::never())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->listener->onPreBuild($event);
        $this->assertFalse($parameters->has(GridViewsExtension::GRID_VIEW_ROOT_PARAM));
    }
}
