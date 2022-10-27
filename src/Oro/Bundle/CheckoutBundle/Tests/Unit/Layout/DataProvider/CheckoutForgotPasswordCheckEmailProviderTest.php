<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Layout\DataProvider\CheckoutForgotPasswordCheckEmailProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class CheckoutForgotPasswordCheckEmailProviderTest extends \PHPUnit\Framework\TestCase
{
    private RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack;

    private Session|\PHPUnit\Framework\MockObject\MockObject $session;

    private CheckoutForgotPasswordCheckEmailProvider $provider;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->session = $this->createMock(Session::class);

        $this->provider = new CheckoutForgotPasswordCheckEmailProvider(
            $this->requestStack,
            $this->session
        );
    }

    public function testIsCheckEmailWithoutParameter(): void
    {
        $email = '...@example.org';
        $this->session->expects(self::once())
            ->method('get')
            ->with('oro_customer_user_reset_email')
            ->willReturn($email);
        $this->session->expects(self::once())
            ->method('remove')
            ->with('oro_customer_user_reset_email');

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn(new Request());

        self::assertEquals($email, $this->provider->getCheckEmail());
    }

    public function testIsCheckEmailWithoutEmail(): void
    {
        $email = null;
        $this->session->expects(self::once())
            ->method('get')
            ->with('oro_customer_user_reset_email')
            ->willReturn($email);
        $this->session->expects(self::once())
            ->method('remove')
            ->with('oro_customer_user_reset_email');

        $request = new Request();
        $request->query->add(['isCheckEmail' => true]);
        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        self::assertEquals($email, $this->provider->getCheckEmail());
        self::assertNull($request->query->get('isCheckEmail'));
        self::assertTrue($request->query->get('isForgotPassword'));
    }
}
