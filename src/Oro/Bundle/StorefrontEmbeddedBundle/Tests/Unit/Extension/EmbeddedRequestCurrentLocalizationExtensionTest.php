<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\StorefrontEmbeddedBundle\Extension\EmbeddedRequestCurrentLocalizationExtension;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EmbeddedRequestCurrentLocalizationExtensionTest extends TestCase
{
    private const string EMBEDDED_FRAGMENT_ROUTE = 'oro_storefront_embedded_fragment';

    private RequestStack $requestStack;
    private LocalizationManager&MockObject $localizationManager;
    private ConfigManager&MockObject $configManager;
    private EmbeddedRequestCurrentLocalizationExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->localizationManager = $this->createMock(LocalizationManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->extension = new EmbeddedRequestCurrentLocalizationExtension(
            new EmbeddedRequestChecker($this->requestStack),
            $this->localizationManager,
            $this->configManager
        );
    }

    public function testReturnsNullWhenThereIsNoMainRequest(): void
    {
        $this->configManager->expects(self::never())
            ->method('get');
        $this->localizationManager->expects(self::never())
            ->method('getLocalization');

        self::assertNull($this->extension->getCurrentLocalization());
    }

    public function testReturnsNullWhenMainRequestRouteIsNotEmbeddedFragment(): void
    {
        $request = Request::create('');
        $request->attributes->set('_route', 'oro_shopping_list_frontend_index');
        $request->headers->set('X-Localization-ID', '123');
        $this->requestStack->push($request);

        $this->configManager->expects(self::never())
            ->method('get');
        $this->localizationManager->expects(self::never())
            ->method('getLocalization');

        self::assertNull($this->extension->getCurrentLocalization());
    }

    public function testReturnsNullWhenLocalizationHeaderIsMissing(): void
    {
        $this->requestStack->push($this->createEmbeddedFragmentRequest());

        $this->configManager->expects(self::never())
            ->method('get');
        $this->localizationManager->expects(self::never())
            ->method('getLocalization');

        self::assertNull($this->extension->getCurrentLocalization());
    }

    /**
     * @dataProvider notIntegerLocalizationHeaderDataProvider
     */
    public function testReturnsNullWhenLocalizationHeaderIsNotInteger(string $localizationId): void
    {
        $this->requestStack->push($this->createEmbeddedFragmentRequest($localizationId));

        $this->configManager->expects(self::never())
            ->method('get');
        $this->localizationManager->expects(self::never())
            ->method('getLocalization');

        self::assertNull($this->extension->getCurrentLocalization());
    }

    public function notIntegerLocalizationHeaderDataProvider(): array
    {
        return [
            'empty string' => [''],
            'word' => ['test'],
            'float' => ['1.5'],
            'integer with trailing characters' => ['123abc']
        ];
    }

    public function testReturnsNullWhenLocalizationIsNotEnabled(): void
    {
        $this->requestStack->push($this->createEmbeddedFragmentRequest('123'));

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_locale.enabled_localizations')
            ->willReturn([234, 345]);
        $this->localizationManager->expects(self::never())
            ->method('getLocalization');

        self::assertNull($this->extension->getCurrentLocalization());
    }

    public function testReturnsNullWhenEnabledLocalizationDoesNotExist(): void
    {
        $this->requestStack->push($this->createEmbeddedFragmentRequest('123'));

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

    /**
     * @dataProvider enabledLocalizationIdsDataProvider
     */
    public function testReturnsLocalizationWhenItsIdIsEnabled(array $enabledLocalizationIds): void
    {
        $localization = $this->createMock(Localization::class);

        $this->requestStack->push($this->createEmbeddedFragmentRequest('123'));

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_locale.enabled_localizations')
            ->willReturn($enabledLocalizationIds);
        $this->localizationManager->expects(self::once())
            ->method('getLocalization')
            ->with(self::identicalTo(123))
            ->willReturn($localization);

        self::assertSame($localization, $this->extension->getCurrentLocalization());
    }

    public function enabledLocalizationIdsDataProvider(): array
    {
        return [
            'integer ids' => [[123, 234]],
            'string ids' => [['123', '234']]
        ];
    }

    public function testResolvesLocalizationFromMainRequestWhenSubRequestIsNotEmbeddedFragment(): void
    {
        $localization = $this->createMock(Localization::class);

        $this->requestStack->push($this->createEmbeddedFragmentRequest('123'));

        $subRequest = Request::create('');
        $subRequest->attributes->set('_route', 'oro_shopping_list_frontend_index');
        $this->requestStack->push($subRequest);

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

    private function createEmbeddedFragmentRequest(?string $localizationId = null): Request
    {
        $request = Request::create('');
        $request->attributes->set('_route', self::EMBEDDED_FRAGMENT_ROUTE);
        if ($localizationId !== null) {
            $request->headers->set('X-Localization-ID', $localizationId);
        }

        return $request;
    }
}
