<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Layout\DataProvider\CheckoutForgotPasswordCheckEmailProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class CheckoutForgotPasswordCheckEmailProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestStack;

    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    private $session;

    /**
     * @var CheckoutForgotPasswordCheckEmailProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->session = $this->createMock(Session::class);

        $this->provider = new CheckoutForgotPasswordCheckEmailProvider(
            $this->requestStack,
            $this->session
        );
    }

    public function testIsCheckEmailWithoutParameter()
    {
        $email = '...@example.org';
        $this->session->expects($this->once())
            ->method('get')
            ->with('oro_customer_user_reset_email')
            ->willReturn($email);
        $this->session->expects($this->once())
            ->method('remove')
            ->with('oro_customer_user_reset_email');

        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn(new Request());

        $this->assertEquals($email, $this->provider->getCheckEmail());
    }

    public function testIsCheckEmailWithoutEmail()
    {
        $email = null;
        $this->session->expects($this->once())
            ->method('get')
            ->with('oro_customer_user_reset_email')
            ->willReturn($email);
        $this->session->expects($this->once())
            ->method('remove')
            ->with('oro_customer_user_reset_email');

        $request = new Request();
        $request->query->add(['isCheckEmail' => true]);
        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->assertEquals($email, $this->provider->getCheckEmail());
        $this->assertNull($request->query->get('isCheckEmail'));
        $this->assertTrue($request->query->get('isForgotPassword'));
    }
}
