<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Provider\SignInTargetPathProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\DependencyInjection\Configuration;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\SignInTargetPathProviderInterface;
use Oro\Bundle\SecurityBundle\Util\SameSiteUrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

final class SignInTargetPathProviderTest extends TestCase
{
    private SignInTargetPathProviderInterface&MockObject $innerProvider;
    private ConfigManager&MockObject $configManager;
    private SameSiteUrlHelper&MockObject $sameSiteUrlHelper;
    private UrlMatcherInterface&MockObject $urlMatcher;

    private SignInTargetPathProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(SignInTargetPathProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->sameSiteUrlHelper = $this->createMock(SameSiteUrlHelper::class);
        $this->urlMatcher = $this->createMock(UrlMatcherInterface::class);

        $this->provider = new SignInTargetPathProvider(
            $this->innerProvider,
            $this->configManager,
            $this->sameSiteUrlHelper,
            $this->urlMatcher
        );
    }

    public function testGetTargetPathWhenDonNotLeaveCheckoutIsOff(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::DO_NOT_LEAVE_CHECKOUT))
            ->willReturn(null);

        $this->innerProvider->expects(self::once())
            ->method('getTargetPath')
            ->willReturn(null);

        self::assertNull($this->provider->getTargetPath());
    }

    public function testGetTargetPathWhenNoSameSiteReferer(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::DO_NOT_LEAVE_CHECKOUT))
            ->willReturn(true);

        $this->sameSiteUrlHelper->expects(self::once())
            ->method('getSameSiteReferer')
            ->willReturn('');

        $this->innerProvider->expects(self::once())
            ->method('getTargetPath')
            ->willReturn(null);

        self::assertNull($this->provider->getTargetPath());
    }

    public function testGetTargetPathWhenNoFoundRouterException(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::DO_NOT_LEAVE_CHECKOUT))
            ->willReturn(true);

        $this->sameSiteUrlHelper->expects(self::once())
            ->method('getSameSiteReferer')
            ->willReturn('https://test.com/test');

        $this->urlMatcher->expects(self::once())
            ->method('match')
            ->with('/test')
            ->willThrowException(new ResourceNotFoundException());

        self::assertNull($this->provider->getTargetPath());
    }

    public function testGetTargetPathWhenWrongRoute(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::DO_NOT_LEAVE_CHECKOUT))
            ->willReturn(true);

        $this->sameSiteUrlHelper->expects(self::once())
            ->method('getSameSiteReferer')
            ->willReturn('https://test.com/test');

        $this->urlMatcher->expects(self::once())
            ->method('match')
            ->with('/test')
            ->willReturn(['_route' => 'oro_test_route']);

        $this->innerProvider->expects(self::once())
            ->method('getTargetPath')
            ->willReturn('https://inner-test.com');

        self::assertEquals('https://inner-test.com', $this->provider->getTargetPath());
    }

    public function testGetTargetPath(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::DO_NOT_LEAVE_CHECKOUT))
            ->willReturn(true);

        $this->sameSiteUrlHelper->expects(self::once())
            ->method('getSameSiteReferer')
            ->willReturn('https://test.com/test');

        $this->urlMatcher->expects(self::once())
            ->method('match')
            ->with('/test')
            ->willReturn(['_route' => 'oro_checkout_frontend_checkout']);

        $this->innerProvider->expects(self::never())
            ->method('getTargetPath');

        self::assertEquals('https://test.com/test', $this->provider->getTargetPath());
    }
}
