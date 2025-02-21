<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Provider\CheckoutIdByTargetPathRequestProvider;
use Oro\Bundle\SecurityBundle\Util\SameSiteUrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

final class CheckoutIdByTargetPathRequestProviderTest extends TestCase
{
    private SameSiteUrlHelper&MockObject $sameSiteUrlHelper;
    private RouterInterface&MockObject $router;

    private CheckoutIdByTargetPathRequestProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->sameSiteUrlHelper = $this->createMock(SameSiteUrlHelper::class);
        $this->router = $this->createMock(RouterInterface::class);

        $this->provider = new CheckoutIdByTargetPathRequestProvider(
            $this->sameSiteUrlHelper,
            $this->router
        );
    }

    public function testGetCheckoutIdWithNotValidTargetPathValue(): void
    {
        $request = new Request();
        self::assertNull($this->provider->getCheckoutId($request));

        $request->request->add(['_target_path' => null]);
        self::assertNull($this->provider->getCheckoutId($request));

        $request->request->add(['_target_path' => '']);
        self::assertNull($this->provider->getCheckoutId($request));

        $request->request->add(['_target_path' => 1]);
        self::assertNull($this->provider->getCheckoutId($request));
    }

    public function testGetCheckoutIdWhenNoSameSite(): void
    {
        $request = new Request();
        $request->request->add(['_target_path' => 'http://example.loc/customer/checkout/1']);

        $this->sameSiteUrlHelper->expects(self::once())
            ->method('isSameSiteUrl')
            ->with('http://example.loc/customer/checkout/1', $request)
            ->willReturn(false);

        self::assertNull($this->provider->getCheckoutId($request));
    }

    public function testGetCheckoutIdWhenNoRouteInfo(): void
    {
        $request = new Request();
        $request->request->add(['_target_path' => 'http://example.loc/customer/checkout/1']);

        $this->sameSiteUrlHelper->expects(self::once())
            ->method('isSameSiteUrl')
            ->with('http://example.loc/customer/checkout/1', $request)
            ->willReturn(true);

        $this->router->expects(self::once())
            ->method('match')
            ->with('/customer/checkout/1')
            ->willReturn([]);

        self::assertNull($this->provider->getCheckoutId($request));
    }

    public function testGetCheckoutIdWhenNoMatchedRoute(): void
    {
        $request = new Request();
        $request->request->add(['_target_path' => 'http://example.loc/customer/checkout/1']);

        $this->sameSiteUrlHelper->expects(self::once())
            ->method('isSameSiteUrl')
            ->with('http://example.loc/customer/checkout/1', $request)
            ->willReturn(true);

        $this->router->expects(self::once())
            ->method('match')
            ->with('/customer/checkout/1')
            ->willReturn(['_route' => 'oro_test_route']);

        self::assertNull($this->provider->getCheckoutId($request));
    }

    public function testGetCheckoutIdWhenResourceNotFoundException(): void
    {
        $request = new Request();
        $request->request->add(['_target_path' => 'http://example.loc/customer/checkout/1']);

        $this->sameSiteUrlHelper->expects(self::once())
            ->method('isSameSiteUrl')
            ->with('http://example.loc/customer/checkout/1', $request)
            ->willReturn(true);

        $this->router->expects(self::once())
            ->method('match')
            ->with('/customer/checkout/1')
            ->willThrowException(new ResourceNotFoundException());

        self::assertNull($this->provider->getCheckoutId($request));
    }

    public function testGetCheckoutIdWhenNoRouteIdParameter(): void
    {
        $request = new Request();
        $request->request->add(['_target_path' => 'http://example.loc/customer/checkout/1']);

        $this->sameSiteUrlHelper->expects(self::once())
            ->method('isSameSiteUrl')
            ->with('http://example.loc/customer/checkout/1', $request)
            ->willReturn(true);

        $this->router->expects(self::once())
            ->method('match')
            ->with('/customer/checkout/1')
            ->willReturn(['_route' => 'oro_checkout_frontend_checkout']);

        self::assertNull($this->provider->getCheckoutId($request));
    }

    public function testGetCheckoutId(): void
    {
        $request = new Request();
        $request->request->add(['_target_path' => 'http://example.loc/customer/checkout/1']);

        $this->sameSiteUrlHelper->expects(self::once())
            ->method('isSameSiteUrl')
            ->with('http://example.loc/customer/checkout/1', $request)
            ->willReturn(true);

        $this->router->expects(self::once())
            ->method('match')
            ->with('/customer/checkout/1')
            ->willReturn(['_route' => 'oro_checkout_frontend_checkout', 'id' => 1]);

        self::assertEquals(1, $this->provider->getCheckoutId($request));
    }
}
