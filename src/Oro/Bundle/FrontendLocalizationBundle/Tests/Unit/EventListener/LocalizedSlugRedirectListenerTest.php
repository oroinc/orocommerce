<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\EventListener;

use Oro\Bundle\FrontendLocalizationBundle\EventListener\LocalizedSlugRedirectListener;
use Oro\Bundle\FrontendLocalizationBundle\Helper\LocalizedSlugRedirectHelper;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\Localization as LocalizationStub;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Routing\SlugRedirectMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class LocalizedSlugRedirectListenerTest extends TestCase
{
    private const OLD_URL = '/oro_product';
    private const NEW_URL = 'http://example.com/oro_product_de';

    private SlugRedirectMatcher&MockObject $redirectMatcher;
    private LocalizedSlugRedirectHelper&MockObject $localizedSlugRedirectHelper;
    private UserLocalizationManagerInterface&MockObject $userLocalizationManager;
    private LocalizedSlugRedirectListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->redirectMatcher = $this->createMock(SlugRedirectMatcher::class);
        $this->localizedSlugRedirectHelper = $this->createMock(LocalizedSlugRedirectHelper::class);
        $this->userLocalizationManager = $this->createMock(UserLocalizationManagerInterface::class);

        $this->listener = new LocalizedSlugRedirectListener(
            $this->redirectMatcher,
            $this->localizedSlugRedirectHelper,
            $this->userLocalizationManager
        );
    }

    public function testOnKernelRequestWhenSingleLocalizationIsEnabled(): void
    {
        $event = $this->createEvent($this->createRequest(self::OLD_URL, 1));
        $this->mockEnabledLocalizations(1);

        $this->userLocalizationManager->expects($this->never())
            ->method('getCurrentLocalization');
        $this->redirectMatcher->expects($this->never())
            ->method('match');
        $this->localizedSlugRedirectHelper->expects($this->never())
            ->method('getLocalizedUrl');

        $this->listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * @dataProvider notMovedForVisitorDataProvider
     */
    public function testOnKernelRequestWhenUrlIsNotMovedForVisitor(
        int $slugLocalizationId,
        ?int $visitorLocalizationId
    ): void {
        $event = $this->createEvent($this->createRequest(self::OLD_URL, $slugLocalizationId));
        $this->mockEnabledLocalizations(2);

        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(null !== $visitorLocalizationId ? $this->createLocalization($visitorLocalizationId) : null);
        $this->redirectMatcher->expects($this->never())
            ->method('match');
        $this->localizedSlugRedirectHelper->expects($this->never())
            ->method('getLocalizedUrl');

        $this->listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function notMovedForVisitorDataProvider(): array
    {
        return [
            'visitor localization is unknown' => [
                'slugLocalizationId' => 1,
                'visitorLocalizationId' => null
            ],
            'used slug belongs to the visitor localization' => [
                'slugLocalizationId' => 8,
                'visitorLocalizationId' => 8
            ]
        ];
    }

    public function testOnKernelRequestWhenUrlIsNotARedirectSource(): void
    {
        $event = $this->createEvent($this->createRequest(self::OLD_URL, 1));
        $this->mockEnabledLocalizations(2);

        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($this->createLocalization(8));
        $this->redirectMatcher->expects($this->once())
            ->method('match')
            ->with(self::OLD_URL)
            ->willReturn(null);
        $this->localizedSlugRedirectHelper->expects($this->never())
            ->method('getLocalizedUrl');

        $this->listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestWhenVisitorLocalizationKeepsTheSameUrl(): void
    {
        $event = $this->createEvent($this->createRequest(self::OLD_URL, 1));
        $localization = $this->createLocalization(8);
        $this->mockEnabledLocalizations(2);

        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $this->redirectMatcher->expects($this->once())
            ->method('match')
            ->with(self::OLD_URL)
            ->willReturn(['pathInfo' => '/oro_product_de', 'statusCode' => Response::HTTP_MOVED_PERMANENTLY]);
        $this->localizedSlugRedirectHelper->expects($this->once())
            ->method('getLocalizedUrl')
            ->with(self::OLD_URL, $localization)
            ->willReturn(self::OLD_URL);

        $this->listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * @dataProvider redirectDataProvider
     */
    public function testOnKernelRequestRedirectsToLocalizedUrl(
        ?int $slugLocalizationId,
        string $uri,
        string $expectedTargetUrl
    ): void {
        $event = $this->createEvent($this->createRequest($uri, $slugLocalizationId));
        $localization = $this->createLocalization(8);
        $this->mockEnabledLocalizations(2);

        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $this->redirectMatcher->expects($this->once())
            ->method('match')
            ->with(self::OLD_URL)
            ->willReturn(['pathInfo' => '/oro_product_de', 'statusCode' => Response::HTTP_MOVED_PERMANENTLY]);
        $this->localizedSlugRedirectHelper->expects($this->once())
            ->method('getLocalizedUrl')
            ->with(self::OLD_URL, $localization)
            ->willReturn(self::NEW_URL);

        $this->listener->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($expectedTargetUrl, $response->getTargetUrl());
        $this->assertEquals(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function redirectDataProvider(): array
    {
        return [
            'used slug belongs to another localization' => [
                'slugLocalizationId' => 1,
                'uri' => self::OLD_URL,
                'expectedTargetUrl' => self::NEW_URL
            ],
            'used slug serves every localization' => [
                'slugLocalizationId' => null,
                'uri' => self::OLD_URL,
                'expectedTargetUrl' => self::NEW_URL
            ],
            'query string is kept' => [
                'slugLocalizationId' => 1,
                'uri' => self::OLD_URL . '?foo=bar',
                'expectedTargetUrl' => self::NEW_URL . '?foo=bar'
            ]
        ];
    }

    private function createRequest(string $uri, ?int $slugLocalizationId): Request
    {
        $slug = new Slug();
        if (null !== $slugLocalizationId) {
            $slug->setLocalization($this->createLocalization($slugLocalizationId));
        }

        $request = Request::create($uri);
        $request->attributes->set('_used_slug', $slug);

        return $request;
    }

    private function createEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    private function createLocalization(int $id): Localization
    {
        $localization = new LocalizationStub();
        $localization->setId($id);

        return $localization;
    }

    private function mockEnabledLocalizations(int $count): void
    {
        $localizations = [];
        for ($id = 1; $id <= $count; $id++) {
            $localizations[$id] = $this->createLocalization($id);
        }

        $this->userLocalizationManager->expects($this->once())
            ->method('getEnabledLocalizations')
            ->willReturn($localizations);
    }
}
