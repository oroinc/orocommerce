<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SaleBundle\EventListener\Datagrid\FrontendGuestQuoteGridViewLinkListener;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FrontendGuestQuoteGridViewLinkListenerTest extends TestCase
{
    private FrontendGuestQuoteGridViewLinkListener $listener;
    private TokenAccessorInterface&MockObject $accessor;

    protected function setUp(): void
    {
        $this->accessor = self::createMock(TokenAccessorInterface::class);
        $this->listener = new FrontendGuestQuoteGridViewLinkListener($this->accessor);
    }

    public function testOnBuildBefore(): void
    {
        $this->accessor->expects(self::once())
            ->method('getToken')
            ->willReturn(new AnonymousCustomerUserToken(new CustomerVisitor()));

        $gridConfiguration = self::createMock(DatagridConfiguration::class);
        $gridConfiguration->expects(self::once())
            ->method('offsetSetByPath')
            ->with(
                '[properties][view_link]',
                [
                    'type' => 'url',
                    'route' => 'oro_sale_quote_frontend_view_guest',
                    'params' => ['guest_access_id' => 'guestAccessId'],
                ]
            );

        $event = self::createMock(BuildBefore::class);
        $event->expects(self::once())
            ->method('getConfig')
            ->willReturn($gridConfiguration);
        $this->listener->onBuildBefore($event);
    }

    public function testOnBuildBeforeWithInvalidToken(): void
    {
        $token = self::createMock(TokenInterface::class);
        $this->accessor->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $gridConfiguration = self::createMock(DatagridConfiguration::class);
        $gridConfiguration->expects(self::never())
            ->method('offsetSetByPath');

        $event = self::createMock(BuildBefore::class);
        $event->expects(self::never())
            ->method('getConfig');

        $this->listener->onBuildBefore($event);
    }
}
