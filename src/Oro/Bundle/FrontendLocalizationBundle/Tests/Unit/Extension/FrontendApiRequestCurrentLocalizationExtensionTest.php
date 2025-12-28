<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Extension;

use Oro\Bundle\ApiBundle\Exception\InvalidHeaderValueException;
use Oro\Bundle\ApiBundle\Request\ApiRequestHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\FrontendLocalizationBundle\Extension\FrontendApiRequestCurrentLocalizationExtension;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class FrontendApiRequestCurrentLocalizationExtensionTest extends TestCase
{
    private RequestStack $requestStack;
    private LocalizationManager&MockObject $localizationManager;
    private ConfigManager&MockObject $configManager;
    private FrontendHelper&MockObject $frontendHelper;
    private ApiRequestHelper&MockObject $apiRequestHelper;
    private FrontendApiRequestCurrentLocalizationExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->localizationManager = $this->createMock(LocalizationManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->apiRequestHelper = $this->createMock(ApiRequestHelper::class);

        $this->extension = new FrontendApiRequestCurrentLocalizationExtension(
            $this->requestStack,
            $this->localizationManager,
            $this->configManager,
            $this->frontendHelper,
            $this->apiRequestHelper
        );
    }

    public function testGetCurrentLocalizationWithoutMainRequest(): void
    {
        $this->frontendHelper->expects(self::never())
            ->method('isFrontendUrl');
        $this->apiRequestHelper->expects(self::never())
            ->method('isApiRequest');
        $this->configManager->expects(self::never())
            ->method('get');
        $this->localizationManager->expects(self::never())
            ->method('getLocalization');

        self::assertNull($this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationForNotFrontendRequest(): void
    {
        $request = Request::create('/api/test');
        $request->headers->set('X-Localization-ID', '1');
        $this->requestStack->push($request);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with('/api/test')
            ->willReturn(false);
        $this->apiRequestHelper->expects(self::never())
            ->method('isApiRequest');
        $this->configManager->expects(self::never())
            ->method('get');
        $this->localizationManager->expects(self::never())
            ->method('getLocalization');

        self::assertNull($this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationForNotApiRequest(): void
    {
        $request = Request::create('/test');
        $request->headers->set('X-Localization-ID', '1');
        $this->requestStack->push($request);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with('/test')
            ->willReturn(true);
        $this->apiRequestHelper->expects(self::once())
            ->method('isApiRequest')
            ->with('/test')
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('get');
        $this->localizationManager->expects(self::never())
            ->method('getLocalization');

        self::assertNull($this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationWithoutLocaleHeader(): void
    {
        $request = Request::create('/api/test');
        $this->requestStack->push($request);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with('/api/test')
            ->willReturn(true);
        $this->apiRequestHelper->expects(self::once())
            ->method('isApiRequest')
            ->with('/api/test')
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('get');
        $this->localizationManager->expects(self::never())
            ->method('getLocalization');

        self::assertNull($this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationWithEmptyLocalizationHeader(): void
    {
        $request = Request::create('/api/test');
        $request->headers->set('X-Localization-ID', '');
        $this->requestStack->push($request);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with('/api/test')
            ->willReturn(true);
        $this->apiRequestHelper->expects(self::once())
            ->method('isApiRequest')
            ->with('/api/test')
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('get');
        $this->localizationManager->expects(self::never())
            ->method('getLocalization');

        self::assertNull($this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationWithNotIntegerLocalizationHeader(): void
    {
        $request = Request::create('/api/test');
        $request->headers->set('X-Localization-ID', 'test');
        $this->requestStack->push($request);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with('/api/test')
            ->willReturn(true);
        $this->apiRequestHelper->expects(self::once())
            ->method('isApiRequest')
            ->with('/api/test')
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('get');
        $this->localizationManager->expects(self::never())
            ->method('getLocalization');

        $this->expectException(InvalidHeaderValueException::class);
        $this->expectExceptionMessage('Expected integer value. Given "test". Header: X-Localization-ID.');

        $this->extension->getCurrentLocalization();
    }

    public function testGetCurrentLocalizationForNotEnabledLocalization(): void
    {
        $request = Request::create('/api/test');
        $request->headers->set('X-Localization-ID', '123');
        $this->requestStack->push($request);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with('/api/test')
            ->willReturn(true);
        $this->apiRequestHelper->expects(self::once())
            ->method('isApiRequest')
            ->with('/api/test')
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_locale.enabled_localizations')
            ->willReturn([234, 345]);
        $this->localizationManager->expects(self::never())
            ->method('getLocalization');

        $this->expectException(InvalidHeaderValueException::class);
        $this->expectExceptionMessage(
            'The value "123" is unknown localization ID. Available values: 234, 345. Header: X-Localization-ID.'
        );

        $this->extension->getCurrentLocalization();
    }

    public function testGetCurrentLocalizationWhenEnabledLocalizationDoesNotExist(): void
    {
        $request = Request::create('/api/test');
        $request->headers->set('X-Localization-ID', '123');
        $this->requestStack->push($request);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with('/api/test')
            ->willReturn(true);
        $this->apiRequestHelper->expects(self::once())
            ->method('isApiRequest')
            ->with('/api/test')
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_locale.enabled_localizations')
            ->willReturn([123, 234]);
        $this->localizationManager->expects(self::once())
            ->method('getLocalization')
            ->with(self::identicalTo(123))
            ->willReturn(null);

        self::assertNull($this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationWithLocalizationHeader(): void
    {
        $localization = $this->createMock(Localization::class);

        $request = Request::create('/api/test');
        $request->headers->set('X-Localization-ID', '123');
        $this->requestStack->push($request);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with('/api/test')
            ->willReturn(true);
        $this->apiRequestHelper->expects(self::once())
            ->method('isApiRequest')
            ->with('/api/test')
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_locale.enabled_localizations')
            ->willReturn([123, 234]);
        $this->localizationManager->expects(self::once())
            ->method('getLocalization')
            ->with(self::identicalTo(123))
            ->willReturn($localization);

        self::assertSame($localization, $this->extension->getCurrentLocalization());
    }
}
