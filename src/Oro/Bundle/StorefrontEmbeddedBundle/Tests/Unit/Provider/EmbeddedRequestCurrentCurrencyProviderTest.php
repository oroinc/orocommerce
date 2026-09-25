<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedRequestCurrentCurrencyProvider;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EmbeddedRequestCurrentCurrencyProviderTest extends TestCase
{
    private const string EMBEDDED_FRAGMENT_ROUTE = 'oro_storefront_embedded_fragment';

    private RequestStack $requestStack;
    private UserCurrencyManager&MockObject $userCurrencyManager;
    private EmbeddedRequestCurrentCurrencyProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);

        $this->provider = new EmbeddedRequestCurrentCurrencyProvider(
            new EmbeddedRequestChecker($this->requestStack),
            $this->userCurrencyManager
        );
    }

    public function testReturnsNullWhenThereIsNoMainRequest(): void
    {
        $this->userCurrencyManager->expects(self::never())
            ->method('getAvailableCurrencies');

        self::assertNull($this->provider->getCurrentCurrency());
    }

    public function testReturnsNullWhenMainRequestRouteIsNotEmbeddedFragment(): void
    {
        $request = Request::create('');
        $request->attributes->set('_route', 'oro_shopping_list_frontend_index');
        $request->headers->set('X-Currency', 'USD');
        $this->requestStack->push($request);

        $this->userCurrencyManager->expects(self::never())
            ->method('getAvailableCurrencies');

        self::assertNull($this->provider->getCurrentCurrency());
    }

    public function testReturnsNullWhenCurrencyHeaderIsMissing(): void
    {
        $this->requestStack->push($this->createEmbeddedFragmentRequest());

        $this->userCurrencyManager->expects(self::never())
            ->method('getAvailableCurrencies');

        self::assertNull($this->provider->getCurrentCurrency());
    }

    public function testReturnsNullWhenCurrencyIsNotAvailable(): void
    {
        $this->requestStack->push($this->createEmbeddedFragmentRequest('CAD'));

        $this->userCurrencyManager->expects(self::once())
            ->method('getAvailableCurrencies')
            ->willReturn(['USD', 'EUR']);

        self::assertNull($this->provider->getCurrentCurrency());
    }

    public function testReturnsCurrencyWhenItIsAvailable(): void
    {
        $this->requestStack->push($this->createEmbeddedFragmentRequest('EUR'));

        $this->userCurrencyManager->expects(self::once())
            ->method('getAvailableCurrencies')
            ->willReturn(['USD', 'EUR']);

        self::assertSame('EUR', $this->provider->getCurrentCurrency());
    }

    public function testResolvesCurrencyFromMainRequestWhenSubRequestIsNotEmbeddedFragment(): void
    {
        $this->requestStack->push($this->createEmbeddedFragmentRequest('EUR'));

        $subRequest = Request::create('');
        $subRequest->attributes->set('_route', 'oro_shopping_list_frontend_index');
        $this->requestStack->push($subRequest);

        $this->userCurrencyManager->expects(self::once())
            ->method('getAvailableCurrencies')
            ->willReturn(['USD', 'EUR']);

        self::assertSame('EUR', $this->provider->getCurrentCurrency());
    }

    private function createEmbeddedFragmentRequest(?string $currency = null): Request
    {
        $request = Request::create('');
        $request->attributes->set('_route', self::EMBEDDED_FRAGMENT_ROUTE);
        if ($currency !== null) {
            $request->headers->set('X-Currency', $currency);
        }

        return $request;
    }
}
